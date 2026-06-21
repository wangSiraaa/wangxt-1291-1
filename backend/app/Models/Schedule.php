<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 0;
    const STATUS_OPEN = 1;
    const STATUS_CLOSED = 2;
    const STATUS_ONGOING = 3;
    const STATUS_ENDED = 4;
    const STATUS_CANCELLED = 5;

    const INHERITOR_PENDING = 0;
    const INHERITOR_CONFIRMED = 1;
    const INHERITOR_REJECTED = 2;

    protected $fillable = [
        'activity_id',
        'inheritor_id',
        'start_time',
        'end_time',
        'max_students',
        'registered_count',
        'notice',
        'inheritor_confirmed',
        'inheritor_remark',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function inheritor()
    {
        return $this->belongsTo(Inheritor::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function canOpenRegistration()
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if ($this->inheritor_confirmed !== self::INHERITOR_CONFIRMED) {
            return false;
        }

        $activity = $this->activity;
        if ($activity && $activity->material_package_id) {
            $materialPackage = $activity->materialPackage;
            if (!$materialPackage || $materialPackage->stock_quantity <= 0) {
                return false;
            }
            if ($materialPackage->stock_quantity < $this->max_students) {
                return false;
            }
        }

        return true;
    }

    public function hasAvailableSlots()
    {
        return $this->registered_count < $this->max_students;
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待发布',
            self::STATUS_OPEN => '可报名',
            self::STATUS_CLOSED => '报名结束',
            self::STATUS_ONGOING => '进行中',
            self::STATUS_ENDED => '已结束',
            self::STATUS_CANCELLED => '已取消',
            default => '未知',
        };
    }

    public function getInheritorConfirmedTextAttribute()
    {
        return match ($this->inheritor_confirmed) {
            self::INHERITOR_PENDING => '待确认',
            self::INHERITOR_CONFIRMED => '已确认',
            self::INHERITOR_REJECTED => '已拒绝',
            default => '未知',
        };
    }
}
