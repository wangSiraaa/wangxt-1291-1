<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Activity;
use App\Models\Inheritor;
use App\Models\StockRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = Schedule::with(['activity:id,title,fee', 'inheritor:id,name,craft_type']);

        if ($request->filled('activity_id')) {
            $query->where('activity_id', $request->activity_id);
        }

        if ($request->filled('inheritor_id')) {
            $query->where('inheritor_id', $request->inheritor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('inheritor_confirmed')) {
            $query->where('inheritor_confirmed', $request->inheritor_confirmed);
        }

        if ($request->filled('date')) {
            $query->whereDate('start_time', $request->date);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_time', [$request->start_date, $request->end_date . ' 23:59:59']);
        }

        $schedules = $query->orderBy('start_time', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $schedules,
        ]);
    }

    public function show($id)
    {
        $schedule = Schedule::with([
            'activity',
            'inheritor',
            'registrations' => function ($q) {
                $q->with('student:id,name,phone')->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $schedule,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'inheritor_id' => 'nullable|exists:inheritors,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'max_students' => 'required|integer|min:1',
            'notice' => 'nullable|string',
        ]);

        $activity = Activity::findOrFail($validated['activity_id']);

        if ($activity->material_package_id) {
            $materialPackage = $activity->materialPackage;
            if (!$materialPackage || $materialPackage->stock_quantity < $validated['max_students']) {
                throw ValidationException::withMessages([
                    'max_students' => ['材料包库存不足，当前库存：' . ($materialPackage->stock_quantity ?? 0)],
                ]);
            }
        }

        if (!empty($validated['inheritor_id'])) {
            $inheritor = Inheritor::findOrFail($validated['inheritor_id']);
            if ($inheritor->hasTimeConflict($validated['start_time'], $validated['end_time'])) {
                throw ValidationException::withMessages([
                    'inheritor_id' => ['该传承人在此时间段已有排期'],
                ]);
            }
        }

        $schedule = Schedule::create(array_merge($validated, [
            'registered_count' => 0,
            'inheritor_confirmed' => Schedule::INHERITOR_PENDING,
            'status' => Schedule::STATUS_PENDING,
        ]));

        return response()->json([
            'code' => 0,
            'message' => '排期创建成功',
            'data' => $schedule,
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        if ($schedule->status > Schedule::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'id' => ['排期已发布，无法修改'],
            ]);
        }

        $validated = $request->validate([
            'activity_id' => 'sometimes|required|exists:activities,id',
            'inheritor_id' => 'nullable|exists:inheritors,id',
            'start_time' => 'sometimes|required|date|after:now',
            'end_time' => 'sometimes|required|date|after:start_time',
            'max_students' => 'sometimes|required|integer|min:1',
            'notice' => 'nullable|string',
        ]);

        if (!empty($validated['inheritor_id']) && 
            ($schedule->inheritor_id != $validated['inheritor_id'] || 
             !empty($validated['start_time']) || 
             !empty($validated['end_time']))) {
            
            $inheritorId = $validated['inheritor_id'] ?? $schedule->inheritor_id;
            $startTime = $validated['start_time'] ?? $schedule->start_time;
            $endTime = $validated['end_time'] ?? $schedule->end_time;

            $inheritor = Inheritor::findOrFail($inheritorId);
            if ($inheritor->hasTimeConflict($startTime, $endTime, $schedule->id)) {
                throw ValidationException::withMessages([
                    'inheritor_id' => ['该传承人在此时间段已有排期'],
                ]);
            }
        }

        $schedule->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '排期更新成功',
            'data' => $schedule,
        ]);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);

        if ($schedule->status > Schedule::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'id' => ['排期已发布，无法删除'],
            ]);
        }

        if ($schedule->registered_count > 0) {
            throw ValidationException::withMessages([
                'id' => ['已有学员报名，无法删除'],
            ]);
        }

        $schedule->delete();

        return response()->json([
            'code' => 0,
            'message' => '排期删除成功',
        ]);
    }

    public function confirm(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'confirmed' => 'required|in:1,2',
            'remark' => 'nullable|string',
        ]);

        $schedule->update([
            'inheritor_confirmed' => $validated['confirmed'],
            'inheritor_remark' => $validated['remark'] ?? null,
        ]);

        return response()->json([
            'code' => 0,
            'message' => $validated['confirmed'] == 1 ? '排期已确认' : '排期已拒绝',
            'data' => $schedule,
        ]);
    }

    public function openRegistration($id)
    {
        $schedule = Schedule::findOrFail($id);

        if (!$schedule->canOpenRegistration()) {
            throw ValidationException::withMessages([
                'id' => ['不满足开放报名条件，请检查传承人确认状态和材料包库存'],
            ]);
        }

        $schedule->update(['status' => Schedule::STATUS_OPEN]);

        return response()->json([
            'code' => 0,
            'message' => '报名已开放',
            'data' => $schedule,
        ]);
    }

    public function closeRegistration($id)
    {
        $schedule = Schedule::findOrFail($id);

        $schedule->update(['status' => Schedule::STATUS_CLOSED]);

        return response()->json([
            'code' => 0,
            'message' => '报名已关闭',
            'data' => $schedule,
        ]);
    }

    public function start($id)
    {
        $schedule = Schedule::findOrFail($id);

        $schedule->update(['status' => Schedule::STATUS_ONGOING]);

        return response()->json([
            'code' => 0,
            'message' => '活动已开始',
            'data' => $schedule,
        ]);
    }

    public function end($id)
    {
        $schedule = Schedule::findOrFail($id);

        \DB::transaction(function () use ($schedule) {
            if ($schedule->activity->material_package_id && $schedule->registered_count > 0) {
                $materialPackage = $schedule->activity->materialPackage;
                $materialPackage->updateStock(
                    -$schedule->registered_count,
                    StockRecord::TYPE_STOCK_OUT,
                    'system',
                    '活动结束扣减材料包库存',
                    'schedule',
                    $schedule->id
                );
            }

            $schedule->update(['status' => Schedule::STATUS_ENDED]);
        });

        return response()->json([
            'code' => 0,
            'message' => '活动已结束',
            'data' => $schedule,
        ]);
    }

    public function cancel($id)
    {
        $schedule = Schedule::findOrFail($id);

        $schedule->registrations()->update(['status' => 0]);

        $schedule->update(['status' => Schedule::STATUS_CANCELLED]);

        return response()->json([
            'code' => 0,
            'message' => '排期已取消',
            'data' => $schedule,
        ]);
    }
}
