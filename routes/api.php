<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NoteQueryController;
//所有接口需要登录认证（sanctum中间件）
Route::middleware('auth:sanctum')->group(function(){
    //分类模块接口（自动生成CRUD路由）
    Route::apiResource('categories', CategoryController::class);
    //笔记模块接口
    Route::apiResource('notes', NoteController::class);
    //笔记状态切换专属接口（PATCH方法）
    Route::patch('notes/{id}/status', [NoteController::class, 'switchStatus']);
});


// 广场笔记查询（公开接口）
Route::get('notes/public', [NoteController::class, 'publicNotes']);

// 高级搜索（可根据需要决定是否公开）
Route::get('notes/advanced-search', [NoteController::class, 'advancedSearch']);

// 需要认证的路由
Route::middleware('auth:api')->group(function () {
    // 个人笔记查询（需要登录）
    Route::get('my-notes', [NoteController::class, 'myNotes']);
});
