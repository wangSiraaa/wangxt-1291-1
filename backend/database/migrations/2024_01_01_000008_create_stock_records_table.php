<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_package_id');
            $table->tinyInteger('type')->comment('1: 入库, 2: 出库, 3: 盘点, 4: 调拨');
            $table->integer('quantity_change')->comment('变动数量，正数增加，负数减少');
            $table->integer('stock_before')->comment('变动前库存');
            $table->integer('stock_after')->comment('变动后库存');
            $table->string('operator')->nullable()->comment('操作人');
            $table->text('remark')->nullable();
            $table->string('related_type', 50)->nullable()->comment('关联类型');
            $table->unsignedBigInteger('related_id')->nullable()->comment('关联ID');
            $table->timestamps();

            $table->foreign('material_package_id')->references('id')->on('material_packages')->cascadeOnDelete();
            $table->index(['material_package_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_records');
    }
};
