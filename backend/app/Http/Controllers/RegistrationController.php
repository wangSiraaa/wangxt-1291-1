<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = Registration::with([
            'schedule' => function ($q) {
                $q->with(['activity:id,title', 'inheritor:id,name']);
            },
            'student:id,name,phone'
        ]);

        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->schedule_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('checkin_status')) {
            $query->where('checkin_status', $request->checkin_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('phone', 'like', "%{$request->keyword}%");
            });
        }

        $registrations = $query->orderBy('id', 'desc')->paginate($request->page_size ?? 15);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $registrations,
        ]);
    }

    public function show($id)
    {
        $registration = Registration::with([
            'schedule' => function ($q) {
                $q->with(['activity', 'inheritor']);
            },
            'student',
            'work'
        ])->findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $registration,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'student_id' => 'required|exists:students,id',
            'fee' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'remark' => 'nullable|string',
        ]);

        $schedule = Schedule::findOrFail($validated['schedule_id']);
        $student = Student::findOrFail($validated['student_id']);

        if ($schedule->status !== Schedule::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'schedule_id' => ['该排期未开放报名'],
            ]);
        }

        if (!$schedule->hasAvailableSlots()) {
            throw ValidationException::withMessages([
                'schedule_id' => ['该排期报名已满'],
            ]);
        }

        if ($student->isRegisteredForSchedule($validated['schedule_id'])) {
            throw ValidationException::withMessages([
                'student_id' => ['该学员已报名此排期'],
            ]);
        }

        $registration = Registration::create(array_merge($validated, [
            'payment_status' => Registration::PAYMENT_UNPAID,
            'checkin_status' => Registration::CHECKIN_NO,
            'status' => 1,
        ]));

        return response()->json([
            'code' => 0,
            'message' => '报名成功',
            'data' => $registration,
        ]);
    }

    public function update(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);

        $validated = $request->validate([
            'fee' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
        ]);

        $registration->update($validated);

        return response()->json([
            'code' => 0,
            'message' => '报名信息更新成功',
            'data' => $registration,
        ]);
    }

    public function destroy($id)
    {
        $registration = Registration::findOrFail($id);

        if ($registration->checkin_status == Registration::CHECKIN_YES) {
            throw ValidationException::withMessages([
                'id' => ['学员已签到，无法取消报名'],
            ]);
        }

        if ($registration->work) {
            throw ValidationException::withMessages([
                'id' => ['学员已提交作品，无法取消报名'],
            ]);
        }

        $registration->delete();

        return response()->json([
            'code' => 0,
            'message' => '报名已取消',
        ]);
    }

    public function pay(Request $request, $id)
    {
        $registration = Registration::findOrFail($id);

        if ($registration->payment_status == Registration::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'id' => ['该报名已支付'],
            ]);
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
        ]);

        $registration->update(array_merge($validated, [
            'payment_status' => Registration::PAYMENT_PAID,
            'paid_at' => now(),
        ]));

        return response()->json([
            'code' => 0,
            'message' => '支付成功',
            'data' => $registration,
        ]);
    }

    public function checkin($id)
    {
        $registration = Registration::findOrFail($id);

        if ($registration->status !== 1) {
            throw ValidationException::withMessages([
                'id' => ['报名已取消，无法签到'],
            ]);
        }

        if ($registration->checkin_status == Registration::CHECKIN_YES) {
            throw ValidationException::withMessages([
                'id' => ['该学员已签到'],
            ]);
        }

        $registration->update([
            'checkin_status' => Registration::CHECKIN_YES,
            'checked_in_at' => now(),
        ]);

        return response()->json([
            'code' => 0,
            'message' => '签到成功',
            'data' => $registration,
        ]);
    }

    public function refund($id)
    {
        $registration = Registration::findOrFail($id);

        if ($registration->payment_status !== Registration::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'id' => ['该报名未支付，无法退款'],
            ]);
        }

        if ($registration->checkin_status == Registration::CHECKIN_YES) {
            throw ValidationException::withMessages([
                'id' => ['学员已签到，无法退款'],
            ]);
        }

        $registration->update([
            'payment_status' => Registration::PAYMENT_REFUNDED,
            'status' => 0,
        ]);

        return response()->json([
            'code' => 0,
            'message' => '退款成功',
            'data' => $registration,
        ]);
    }

    public function canSubmitWork($id)
    {
        $registration = Registration::findOrFail($id);

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'can_submit' => $registration->canSubmitWork(),
            ],
        ]);
    }
}
