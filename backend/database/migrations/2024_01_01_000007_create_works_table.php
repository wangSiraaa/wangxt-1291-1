<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('作品名称');
            $table->text('description')->nullable()->comment('作品描述');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('inheritor_id')->nullable();
            $table->json('images')->nullable()->comment('作品照片（JSON数组）');
            $table->text('inheritor_comment')->nullable()->comment('传承人点评');
            $table->integer('score')->nullable()->comment('评分（0-100）');
            $table->tinyInteger('is_excellent')->default(0)->comment('是否优秀作品');
            $table->tinyInteger('is_public')->default(0)->comment('是否公开展示');
            $table->text('admin_remark')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: 待审核, 2: 已审核, 3: 已驳回');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('registration_id')->references('id')->on('registrations')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
            $table->foreign('inheritor_id')->references('id')->on('inheritors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
