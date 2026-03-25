<?php
use Illuminate\Http\Request;
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


// 广场笔记查询（公开接口）
Route::get('/notes/public', [NoteQueryController::class, 'publicNotes']);

// 高级搜索（可根据需要决定是否公开）
Route::get('notes/advanced-search', [NoteQueryController::class, 'advancedSearch']);

// 需要认证的路由
Route::middleware('auth:api')->group(function () {
    // 个人笔记查询（需要登录）
    Route::get('/my-notes', [NoteQueryController::class, 'myNotes']);
});