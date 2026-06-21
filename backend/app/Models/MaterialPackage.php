<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku_code',
        'description',
        'items',
        'stock_quantity',
        'warning_quantity',
        'cost',
        'image',
        'status',
    ];

    protected $casts = [
        'items' => 'array',
        'status' => 'boolean',
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function stockRecords()
    {
        return $this->hasMany(StockRecord::class);
    }

    public function isLowStock()
    {
        return $this->stock_quantity <= $this->warning_quantity;
    }

    public function updateStock($quantityChange, $type, $operator = null, $remark = null, $relatedType = null, $relatedId = null)
    {
        $stockBefore = $this->stock_quantity;
        $stockAfter = $stockBefore + $quantityChange;

        if ($stockAfter < 0) {
            throw new \Exception('库存不足');
        }

        \DB::transaction(function () use ($quantityChange, $type, $operator, $remark, $relatedType, $relatedId, $stockBefore, $stockAfter) {
            $this->update(['stock_quantity' => $stockAfter]);

            $this->stockRecords()->create([
                'type' => $type,
                'quantity_change' => $quantityChange,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'operator' => $operator,
                'remark' => $remark,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
            ]);
        });

        return $this;
    }
}
