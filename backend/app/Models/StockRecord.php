<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRecord extends Model
{
    use HasFactory;

    const TYPE_STOCK_IN = 1;
    const TYPE_STOCK_OUT = 2;
    const TYPE_INVENTORY = 3;
    const TYPE_TRANSFER = 4;

    protected $fillable = [
        'material_package_id',
        'type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'operator',
        'remark',
        'related_type',
        'related_id',
    ];

    public function materialPackage()
    {
        return $this->belongsTo(MaterialPackage::class);
    }

    public function getTypeTextAttribute()
    {
        return match ($this->type) {
            self::TYPE_STOCK_IN => '入库',
            self::TYPE_STOCK_OUT => '出库',
            self::TYPE_INVENTORY => '盘点',
            self::TYPE_TRANSFER => '调拨',
            default => '未知',
        };
    }
}
