<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registration extends Model
{
    use HasFactory, SoftDeletes;

    const PAYMENT_UNPAID = 0;
    const PAYMENT_PAID = 1;
    const PAYMENT_REFUNDED = 2;

    const CHECKIN_NO = 0;
    const CHECKIN_YES = 1;

    protected $fillable = [
        'registration_no',
        'schedule_id',
        'student_id',
        'fee',
        'payment_status',
        'paid_at',
        'payment_method',
        'transaction_id',
        'checkin_status',
        'checked_in_at',
        'remark',
        'status',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'paid_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($registration) {
            if (empty($registration->registration_no)) {
                $registration->registration_no = 'REG' . date('YmdHis') . rand(1000, 9999);
            }
        });

        static::created(function ($registration) {
            $registration->schedule()->increment('registered_count');
        });

        static::deleted(function ($registration) {
            if ($registration->schedule) {
                $registration->schedule()->decrement('registered_count');
            }
        });
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function work()
    {
        return $this->hasOne(Work::class);
    }

    public function canSubmitWork()
    {
        return $this->checkin_status === self::CHECKIN_YES 
            && $this->status === 1
            && !$this->work;
    }

    public function getPaymentStatusTextAttribute()
    {
        return match ($this->payment_status) {
            self::PAYMENT_UNPAID => '未支付',
            self::PAYMENT_PAID => '已支付',
            self::PAYMENT_REFUNDED => '已退款',
            default => '未知',
        };
    }

    public function getCheckinStatusTextAttribute()
    {
        return match ($this->checkin_status) {
            self::CHECKIN_NO => '未签到',
            self::CHECKIN_YES => '已签到',
            default => '未知',
        };
    }
}
