<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // 标题
            $table->text('content'); // 内容
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 关联用户
            $table->foreignId('category_id')->constrained()->onDelete('cascade'); // 关联分类
            $table->boolean('is_public')->default(false); // 公开状态，默认私有
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
