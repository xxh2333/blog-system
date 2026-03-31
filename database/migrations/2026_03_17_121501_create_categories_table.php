<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 创建 categories 表（分类表）
        Schema::create('categories', function (Blueprint $table) {
            $table->id()->comment('分类ID，主键'); // 主键ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');// 关联用户表
            $table->comment('所属用户ID，关联users表，用户删除时级联删除');
            $table->string('name')->comment('分类名称'); // 分类名称
            $table->timestamps(); // 创建时间/更新时间

            //表注释
            $table->comment('笔记分类表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 删除 categories 表
        Schema::dropIfExists('categories');
    }
};