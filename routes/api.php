<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 引入NoteController控制器
use App\Http\Controllers\Api\NoteController;

// 原有路由（用户/分类）保留，此处添加笔记路由↓

// 笔记模块核心路由
// 1. 广场公开笔记：GET /api/notes/public
Route::get('notes/public', [NoteController::class, 'publicNotes']);
// 2. 个人笔记：GET /api/my-notes
Route::get('my-notes', [NoteController::class, 'myNotes']);
// 3. 笔记CRUD基础路由：自动映射store/show/update/destroy（无需手动写4条路由）
Route::apiResource('notes', NoteController::class);
// 4. 切换笔记状态：PATCH /api/notes/{id}/status
Route::patch('notes/{note}/status', [NoteController::class, 'toggleStatus']);
