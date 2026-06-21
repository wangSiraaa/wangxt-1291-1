<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('id_card', 18)->unique()->nullable();
            $table->date('birthday')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact')->nullable()->comment('紧急联系人');
            $table->string('emergency_phone', 20)->nullable()->comment('紧急联系电话');
            $table->text('remark')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: 正常, 0: 拉黑');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
