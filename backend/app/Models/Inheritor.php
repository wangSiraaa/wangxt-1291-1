<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inheritor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'id_card',
        'craft_type',
        'bio',
        'avatar',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function hasTimeConflict($startTime, $endTime, $excludeScheduleId = null)
    {
        $query = $this->schedules()
            ->whereNotIn('status', [4, 5])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return $query->exists();
    }
}
