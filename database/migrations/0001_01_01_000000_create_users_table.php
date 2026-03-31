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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('用户ID，主键');
            $table->string('name')->comment('用户名称');
            $table->string('email')->unique()->comment('用户邮箱，唯一');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间');
            $table->string('password')->comment('用户密码（加密）');
            $table->rememberToken()->comment('记住登录令牌');
            $table->timestamps();

            // 表注释
            $table->comment('用户信息表');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('用户邮箱');
            $table->string('token')->comment('密码重置令牌');
            $table->timestamp('created_at')->nullable()->comment('创建时间');

            // 表注释
            $table->comment('密码重置令牌表');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('会话ID');
            $table->foreignId('user_id')->nullable()->index()->comment('用户ID（外键）');
            $table->string('ip_address', 45)->nullable()->comment('客户端IP地址');
            $table->text('user_agent')->nullable()->comment('客户端浏览器信息');
            $table->longText('payload')->comment('会话数据');
            $table->integer('last_activity')->index()->comment('最后活跃时间');

            // 表注释
            $table->comment('用户会话表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};