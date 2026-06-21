<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Work extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_PENDING = 1;
    const STATUS_APPROVED = 2;
    const STATUS_REJECTED = 3;

    protected $fillable = [
        'title',
        'description',
        'registration_id',
        'student_id',
        'activity_id',
        'inheritor_id',
        'images',
        'inheritor_comment',
        'score',
        'is_excellent',
        'is_public',
        'admin_remark',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'is_excellent' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function inheritor()
    {
        return $this->belongsTo(Inheritor::class);
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已审核',
            self::STATUS_REJECTED => '已驳回',
            default => '未知',
        };
    }
}
