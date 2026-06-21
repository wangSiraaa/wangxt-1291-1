<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku_code', 50)->unique()->comment('库存编码');
            $table->text('description')->nullable()->comment('材料包描述');
            $table->text('items')->nullable()->comment('包含材料清单（JSON）');
            $table->integer('stock_quantity')->default(0)->comment('库存数量');
            $table->integer('warning_quantity')->default(10)->comment('预警数量');
            $table->decimal('cost', 10, 2)->default(0)->comment('成本价');
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: 启用, 0: 禁用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_packages');
    }
};
