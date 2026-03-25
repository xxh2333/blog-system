<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\CategoryController;
//所有接口需要登录认证（sanctum中间件）
Route::middleware('auth:sanctum')->group(function(){
    //分类模块接口（自动生成CRUD路由）
    Route::apiResource('categories', CategoryController::class);
    //笔记模块接口
    Route::apiResource('notes', NoteController::class);
    //笔记状态切换专属接口（PATCH方法）
    Route::patch('notes/{id}/status', [NoteController::class, 'switchStatus']);
});
