<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkController extends Controller
{
    public function index(Request $request)
    {
        $query = Work::with([
            'student:id,name,phone',
            'activity:id,title',
            'inheritor:id,name',
            'registration:id,registration_no,checkin_status',
        ]);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('activity_id')) {
            $query->where('activity_id', $request->activity_id);
        }

        if ($request->filled('inheritor_id')) {
            $query->where('inheritor_id', $request->inheritor_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_excellent')) {
            $query->where('is_excellent', $request->is_excellent);
        }

        if ($request->filled('is_public')) {
            $query->where('is_public', $request->is_public);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->keyword}%")
                    ->orWhereHas('student', function ($q2) use ($request) {
                        $q2->where('name', 'like', "%{$request->keyword}%");
                    });
            });
        }

        $works = $query->orderBy('id', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $works,
        ]);
    }

    public function show($id)
    {
        $work = Work::with([
            'student',
            'activity',
            'inheritor',
            'registration' => function ($q) {
                $q->with(['schedule' => function ($q2) {
                    $q2->with(['activity', 'inheritor']);
                }]);
            }
        ])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $work,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'registration_id' => 'required|exists:registrations,id',
            'images' => 'required|array|min:1',
            'images.*' => 'string|max:255',
        ]);

        $registration = Registration::with('schedule.activity')->findOrFail($validated['registration_id']);

        if (!$registration->canSubmitWork()) {
            throw ValidationException::withMessages([
                'registration_id' => ['不满足提交作品条件，请检查签到状态'],
            ]);
        }

        $work = Work::create(array_merge($validated, [
            'student_id' => $registration->student_id,
            'activity_id' => $registration->schedule->activity_id,
            'inheritor_id' => $registration->schedule->inheritor_id,
            'status' => Work::STATUS_PENDING,
            'is_excellent' => 0,
            'is_public' => 0,
        ]));

        return response()->json([
            'code' => 0,
            'message' => '作品提交成功',
            'data' => $work,
        ]);
    }

    public function update(Request $request, $id)
    {
        $work = Work::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string',
            'images' => 'nullable|array|min:1',
            'images.*' => 'string|max:255',
        ]);

        if ($work->status == Work::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'id' => ['作品已审核，无法修改'],
            ]);
        }

        $work->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '作品更新成功',
            'data' => $work,
        ]);
    }

    public function destroy($id)
    {
        $work = Work::findOrFail($id);

        if ($work->status == Work::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'id' => ['作品已审核，无法删除'],
            ]);
        }

        $work->delete();

        return response()->json([
            'code' => 0,
            'message' => '作品删除成功',
        ]);
    }

    public function review(Request $request, $id)
    {
        $work = Work::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:2,3',
            'inheritor_comment' => 'nullable|string',
            'score' => 'nullable|integer|min:0|max:100',
            'is_excellent' => 'nullable|boolean',
            'admin_remark' => 'nullable|string',
        ]);

        $work->update($validated);

        return response()->json([
            'code' => 0,
            'message' => $validated['status'] == 2 ? '审核通过' : '审核驳回',
            'data' => $work,
        ]);
    }

    public function setPublic($id)
    {
        $work = Work::findOrFail($id);

        if ($work->status !== Work::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'id' => ['作品未审核，无法公开展示'],
            ]);
        }

        $work->update(['is_public' => 1]);

        return response()->json([
            'code' => 0,
            'message' => '已设置为公开展示',
            'data' => $work,
        ]);
    }

    public function setPrivate($id)
    {
        $work = Work::findOrFail($id);

        $work->update(['is_public' => 0]);

        return response()->json([
            'code' => 0,
            'message' => '已取消公开展示',
            'data' => $work,
        ]);
    }

    public function setExcellent($id)
    {
        $work = Work::findOrFail($id);

        $work->update(['is_excellent' => 1]);

        return response()->json([
            'code' => 0,
            'message' => '已标记为优秀作品',
            'data' => $work,
        ]);
    }

    public function cancelExcellent($id)
    {
        $work = Work::findOrFail($id);

        $work->update(['is_excellent' => 0]);

        return response()->json([
            'code' => 0,
            'message' => '已取消优秀作品标记',
            'data' => $work,
        ]);
    }
}
