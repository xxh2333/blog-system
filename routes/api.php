<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\NoteQueryController;

Route::post('/auth/send-code', [AuthController::class, 'sendCode']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/auth/me', [AuthController::class, 'me']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

// 新增：查看已注册账号
Route::get('/auth/registered-users', [AuthController::class, 'getRegisteredUsers']);


use Illuminate\Http\Request;

// 引入NoteController控制器
use App\Http\Controllers\Api\NoteController;

// 原有路由（用户/分类）保留，此处添加笔记路由↓

// 笔记模块核心路由
// 1. 广场公开笔记：GET /api/notes/public
Route::get('notes/public', [NoteQueryController::class, 'publicNotes']);

// 需要认证的路由
Route::middleware('auth:api')->group(function () {
    // 个人笔记查询（需要登录）
    Route::get('/my-notes', [NoteQueryController::class, 'myNotes']);
});
// 高级搜索（可根据需要决定是否公开）
Route::get('notes/advanced-search', [NoteQueryController::class, 'advancedSearch']);



// 3. 笔记CRUD基础路由：自动映射store/show/update/destroy（无需手动写4条路由）
Route::apiResource('notes', NoteController::class);
// 4. 切换笔记状态：PATCH /api/notes/{id}/status
Route::patch('notes/{note}/status', [NoteController::class, 'toggleStatus']);
