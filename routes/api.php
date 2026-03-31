<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 引入 NoteController 控制器
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;

// 原有路由（用户/分类）保留，此处添加笔记路由↓

// 处理未认证请求
Route::get('unauthorized', function () {
    return response()->json(['message' => '未授权访问'], 401);
});

// 认证路由（无需登录）
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('send-code', [AuthController::class, 'sendCode']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('users', [AuthController::class, 'getRegisteredUsers']);
});

// 需要登录验证的路由
Route::middleware('auth:api')->group(function () {
    // 分类模块路由
    Route::apiResource('categories', CategoryController::class)->except(['edit', 'create']);
    
    // 个人笔记：GET /api/my-notes
    Route::get('my-notes', [NoteController::class, 'myNotes']);
    // 笔记 CRUD 基础路由：自动映射 store/show/update/destroy（无需手动写 4 条路由）
    Route::apiResource('notes', NoteController::class);
    // 切换笔记状态：PATCH /api/notes/{id}/status
    Route::patch('notes/{note}/status', [NoteController::class, 'toggleStatus']);
});

// 公开接口（无需登录）
Route::get('notes/public', [NoteController::class, 'publicNotes']);
