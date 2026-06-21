<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DRAFT = 0;
    const STATUS_PUBLISHED = 1;
    const STATUS_ENDED = 2;
    const STATUS_CANCELLED = 3;

    protected $fillable = [
        'title',
        'description',
        'material_package_id',
        'max_students',
        'fee',
        'duration_minutes',
        'location',
        'cover_image',
        'requirements',
        'status',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
    ];

    public function materialPackage()
    {
        return $this->belongsTo(MaterialPackage::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function canPublish()
    {
        if ($this->material_package_id) {
            $materialPackage = $this->materialPackage;
            if (!$materialPackage || $materialPackage->stock_quantity <= 0) {
                return false;
            }
        }
        return true;
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_ENDED => '已结束',
            self::STATUS_CANCELLED => '已取消',
            default => '未知',
        };
    }
}
