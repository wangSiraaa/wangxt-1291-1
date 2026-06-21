<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id');
            $table->unsignedBigInteger('inheritor_id')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('max_students')->default(20);
            $table->integer('registered_count')->default(0);
            $table->text('notice')->nullable()->comment('排期注意事项');
            $table->tinyInteger('inheritor_confirmed')->default(0)->comment('0: 待确认, 1: 已确认, 2: 已拒绝');
            $table->text('inheritor_remark')->nullable()->comment('传承人备注');
            $table->tinyInteger('status')->default(0)->comment('0: 待发布, 1: 可报名, 2: 报名结束, 3: 进行中, 4: 已结束, 5: 已取消');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
            $table->foreign('inheritor_id')->references('id')->on('inheritors')->nullOnDelete();
            
            $table->index(['inheritor_id', 'start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
