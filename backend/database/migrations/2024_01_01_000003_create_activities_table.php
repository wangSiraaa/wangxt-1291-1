<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('material_package_id')->nullable()->comment('关联材料包');
            $table->integer('max_students')->default(20)->comment('最大报名人数');
            $table->decimal('fee', 10, 2)->default(0)->comment('活动费用');
            $table->integer('duration_minutes')->default(120)->comment('活动时长（分钟）');
            $table->string('location')->nullable()->comment('活动地点');
            $table->string('cover_image')->nullable();
            $table->text('requirements')->nullable()->comment('学员须知');
            $table->tinyInteger('status')->default(0)->comment('0: 草稿, 1: 已发布, 2: 已结束, 3: 已取消');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('material_package_id')->references('id')->on('material_packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
