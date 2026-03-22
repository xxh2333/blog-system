<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\CategoryController;

// 公开接口（无需登录，JWT 认证相关）
Route::post('auth/send-code', [AuthController::class, 'sendCode']); // 发送验证码
Route::post('auth/register', [AuthController::class, 'register']); // 注册
Route::post('auth/login', [AuthController::class, 'login']);       // 登录

// 需登录接口（统一使用 auth:api 中间件，兼容 JWT）
Route::middleware('auth:api')->group(function () {
    // 认证模块接口
    Route::post('auth/logout', [AuthController::class, 'logout']); // 退出
    Route::get('auth/me', [AuthController::class, 'me']);         // 获取用户信息

    // 分类模块接口（自动生成 CRUD 路由）
    Route::apiResource('categories', CategoryController::class);

    // 笔记模块接口
    Route::apiResource('notes', NoteController::class);
    // 笔记状态切换专属接口（PATCH 方法）
    Route::patch('notes/{id}/status', [NoteController::class, 'switchStatus']);
});