<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('materialPackage:id,name,stock_quantity');

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->keyword}%")
                    ->orWhere('description', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('material_package_id')) {
            $query->where('material_package_id', $request->material_package_id);
        }

        $activities = $query->orderBy('id', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $activities,
        ]);
    }

    public function show($id)
    {
        $activity = Activity::with(['materialPackage', 'schedules' => function ($q) {
            $q->with('inheritor:id,name')->orderBy('start_time', 'desc');
        }])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $activity,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'material_package_id' => 'nullable|exists:material_packages,id',
            'max_students' => 'nullable|integer|min:1',
            'fee' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string|max:255',
            'requirements' => 'nullable|string',
            'status' => 'nullable|in:0,1,2,3',
        ]);

        $activity = Activity::create($validated);

        return response()->json([
            'code' => 0,
            'message' => '活动创建成功',
            'data' => $activity,
        ]);
    }

    public function update(Request $request, $id)
    {
        $activity = Activity::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:200',
            'description' => 'nullable|string',
            'material_package_id' => 'nullable|exists:material_packages,id',
            'max_students' => 'nullable|integer|min:1',
            'fee' => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'cover_image' => 'nullable|string|max:255',
            'requirements' => 'nullable|string',
            'status' => 'nullable|in:0,1,2,3',
        ]);

        $activity->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '活动更新成功',
            'data' => $activity,
        ]);
    }

    public function destroy($id)
    {
        $activity = Activity::findOrFail($id);

        $hasActiveSchedules = $activity->schedules()
            ->whereNotIn('status', [4, 5])
            ->exists();

        if ($hasActiveSchedules) {
            throw ValidationException::withMessages([
                'id' => ['该活动存在未完成的排期，无法删除'],
            ]);
        }

        $activity->delete();

        return response()->json([
            'code' => 0,
            'message' => '活动删除成功',
        ]);
    }

    public function publish($id)
    {
        $activity = Activity::findOrFail($id);

        if (!$activity->canPublish()) {
            throw ValidationException::withMessages([
                'id' => ['材料包库存不足，无法发布活动'],
            ]);
        }

        $activity->update(['status' => Activity::STATUS_PUBLISHED]);

        return response()->json([
            'code' => 0,
            'message' => '活动发布成功',
            'data' => $activity,
        ]);
    }

    public function cancel($id)
    {
        $activity = Activity::findOrFail($id);

        $activity->schedules()->update(['status' => 5]);

        $activity->update(['status' => Activity::STATUS_CANCELLED]);

        return response()->json([
            'code' => 0,
            'message' => '活动已取消',
            'data' => $activity,
        ]);
    }

    public function all()
    {
        $activities = Activity::where('status', Activity::STATUS_PUBLISHED)
            ->orderBy('id', 'desc')
            ->get(['id', 'title', 'fee', 'duration_minutes']);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $activities,
        ]);
    }
}
