<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'id_card',
        'birthday',
        'gender',
        'address',
        'emergency_contact',
        'emergency_phone',
        'remark',
        'status',
    ];

    protected $casts = [
        'birthday' => 'date',
        'status' => 'boolean',
        'gender' => 'integer',
    ];

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function isRegisteredForSchedule($scheduleId)
    {
        return $this->registrations()
            ->where('schedule_id', $scheduleId)
            ->where('status', 1)
            ->exists();
    }
}
