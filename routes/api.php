<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// 公开接口（无需登录）
Route::post('auth/send-code', [AuthController::class, 'sendCode']); // 发送验证码
Route::post('auth/register', [AuthController::class, 'register']); // 注册
Route::post('auth/login', [AuthController::class, 'login']);       // 登录

// 需登录接口（JWT保护）
Route::middleware('auth:api')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']); // 退出
    Route::get('auth/me', [AuthController::class, 'me']);         // 获取用户信息
});