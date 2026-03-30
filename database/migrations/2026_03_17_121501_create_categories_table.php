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
            $table->id(); // 主键ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 关联用户表
            $table->string('name'); // 分类名称
            $table->timestamps(); // 创建时间/更新时间
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