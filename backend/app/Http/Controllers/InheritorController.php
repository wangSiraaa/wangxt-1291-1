<?php

namespace App\Http\Controllers;

use App\Models\Inheritor;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InheritorController extends Controller
{
    public function index(Request $request)
    {
        $query = Inheritor::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('phone', 'like', "%{$request->keyword}%")
                    ->orWhere('craft_type', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->filled('craft_type')) {
            $query->where('craft_type', $request->craft_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $inheritors = $query->orderBy('id', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $inheritors,
        ]);
    }

    public function show($id)
    {
        $inheritor = Inheritor::with(['schedules' => function ($q) {
            $q->with('activity')->orderBy('start_time', 'desc');
        }])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $inheritor,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:inheritors,phone',
            'id_card' => 'nullable|string|size:18|unique:inheritors,id_card',
            'craft_type' => 'required|string|max:100',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $inheritor = Inheritor::create($validated);

        return response()->json([
            'code' => 0,
            'message' => '传承人创建成功',
            'data' => $inheritor,
        ]);
    }

    public function update(Request $request, $id)
    {
        $inheritor = Inheritor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'sometimes|required|string|max:20|unique:inheritors,phone,' . $id,
            'id_card' => 'nullable|string|size:18|unique:inheritors,id_card,' . $id,
            'craft_type' => 'sometimes|required|string|max:100',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|string|max:255',
            'status' => 'nullable|boolean',
        ]);

        $inheritor->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '传承人更新成功',
            'data' => $inheritor,
        ]);
    }

    public function destroy($id)
    {
        $inheritor = Inheritor::findOrFail($id);
        
        $hasActiveSchedules = $inheritor->schedules()
            ->whereNotIn('status', [4, 5])
            ->exists();
        
        if ($hasActiveSchedules) {
            throw ValidationException::withMessages([
                'id' => ['该传承人存在未完成的排期，无法删除'],
            ]);
        }

        $inheritor->delete();

        return response()->json([
            'code' => 0,
            'message' => '传承人删除成功',
        ]);
    }

    public function all()
    {
        $inheritors = Inheritor::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'craft_type']);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $inheritors,
        ]);
    }
}
