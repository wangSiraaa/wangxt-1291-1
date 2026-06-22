<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('phone', 'like', "%{$request->keyword}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        $students = $query->orderBy('id', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $students,
        ]);
    }

    public function show($id)
    {
        $student = Student::with([
            'registrations' => function ($q) {
                $q->with(['schedule' => function ($q2) {
                    $q2->with(['activity:id,title', 'inheritor:id,name']);
                }])->orderBy('created_at', 'desc');
            },
            'works' => function ($q) {
                $q->with('activity:id,title')->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $student,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:students,phone',
            'id_card' => 'nullable|string|size:18|unique:students,id_card',
            'birthday' => 'nullable|date',
            'gender' => 'nullable|integer|in:1,2',
            'address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:50',
            'emergency_phone' => 'nullable|string|max:20',
            'remark' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);

        $student = Student::create($validated);

        return response()->json([
            'code' => 0,
            'message' => '学员创建成功',
            'data' => $student,
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'phone' => 'sometimes|required|string|max:20|unique:students,phone,' . $id,
            'id_card' => 'nullable|string|size:18|unique:students,id_card,' . $id,
            'birthday' => 'nullable|date',
            'gender' => 'nullable|integer|in:1,2',
            'address' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:50',
            'emergency_phone' => 'nullable|string|max:20',
            'remark' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);

        $student->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '学员更新成功',
            'data' => $student,
        ]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $hasActiveRegistrations = $student->registrations()
            ->whereHas('schedule', function ($q) {
                $q->whereNotIn('status', [4, 5]);
            })
            ->where('status', 1)
            ->exists();

        if ($hasActiveRegistrations) {
            throw ValidationException::withMessages([
                'id' => ['该学员存在未完成的报名，无法删除'],
            ]);
        }

        $student->delete();

        return response()->json([
            'code' => 0,
            'message' => '学员删除成功',
        ]);
    }

    public function all()
    {
        $students = Student::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $students,
        ]);
    }
}
