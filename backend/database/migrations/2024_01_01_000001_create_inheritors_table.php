<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inheritors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('id_card', 18)->unique()->nullable();
            $table->string('craft_type')->comment('非遗技艺类型');
            $table->text('bio')->nullable()->comment('传承人简介');
            $table->string('avatar')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1: 启用, 0: 禁用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inheritors');
    }
};
