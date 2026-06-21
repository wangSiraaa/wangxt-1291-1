<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no', 32)->unique()->comment('报名编号');
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('fee', 10, 2)->default(0);
            $table->tinyInteger('payment_status')->default(0)->comment('0: 未支付, 1: 已支付, 2: 已退款');
            $table->dateTime('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable()->comment('交易单号');
            $table->tinyInteger('checkin_status')->default(0)->comment('0: 未签到, 1: 已签到');
            $table->dateTime('checked_in_at')->nullable();
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: 报名成功, 0: 已取消');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('schedule_id')->references('id')->on('schedules')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            
            $table->unique(['schedule_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
