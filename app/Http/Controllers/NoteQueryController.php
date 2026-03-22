<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteQueryController extends Controller
{
    /**
     * 个人笔记查询接口
     * GET /api/my-notes
     *
     * 参数说明：
     * - search: 关键词（模糊搜索title）
     * - category_id: 分类ID（筛选指定分类）
     * - per_page: 每页数量（默认15）
     * - page: 页码（默认1）
     */
    public function myNotes(Request $request)
    {
        // 基础查询：只查当前用户的笔记，按创建时间倒序
        $query = auth('api')->user()->notes()
            ->with('category')  // 预加载分类信息
            ->orderBy('created_at', 'desc');

        // 动态模糊搜索：基于title关键词
        // 使用 when() 条件构造，优雅处理可选条件
        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->where('title', 'like', '%' . $request->search . '%');
        });

        // 分类筛选：按分类ID过滤
        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('category_id', $request->category_id);
        });

        // 分页查询
        $perPage = $request->input('per_page', 15);
        $notes = $query->paginate($perPage);

        // 返回统一格式的分页数据
        return response()->json([
            'data' => $notes->items(),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
            'links' => [
                'first' => $notes->url(1),
                'last' => $notes->url($notes->lastPage()),
                'prev' => $notes->previousPageUrl(),
                'next' => $notes->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 广场笔记查询接口
     * GET /api/notes/public
     *
     * 参数说明：
     * - search: 关键词（模糊搜索title）
     * - category_id: 分类ID（筛选指定分类）
     * - per_page: 每页数量（默认15）
     * - page: 页码（默认1）
     */
    public function publicNotes(Request $request)
    {
        // 基础查询：只查公开笔记，预加载用户和分类信息，按创建时间倒序
        $query = Note::with(['user', 'category'])
            ->where('is_public', true)
            ->orderBy('created_at', 'desc');

        // 动态模糊搜索：使用 when() 条件构造
        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->where('title', 'like', '%' . $request->search . '%');
        });

        // 分类筛选
        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('category_id', $request->category_id);
        });

        // 分页查询
        $perPage = $request->input('per_page', 15);
        $notes = $query->paginate($perPage);

        // 返回统一格式的分页数据
        return response()->json([
            'data' => $notes->items(),
            'meta' => [
                'current_page' => $notes->currentPage(),
                'last_page' => $notes->lastPage(),
                'per_page' => $notes->perPage(),
                'total' => $notes->total(),
            ],
            'links' => [
                'first' => $notes->url(1),
                'last' => $notes->url($notes->lastPage()),
                'prev' => $notes->previousPageUrl(),
                'next' => $notes->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 可选：高级查询接口（支持更多筛选条件）
     * GET /api/notes/advanced-search
     */
    public function advancedSearch(Request $request)
    {
        $query = Note::with(['user', 'category'])
            ->orderBy('created_at', 'desc');

        // 权限过滤：如果未认证或非本人，只能看公开笔记
        if (!auth('api')->check()) {
            $query->where('is_public', true);
        } else {
            // 已认证用户：可看自己的所有笔记 + 他人的公开笔记
            $query->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere('user_id', auth('api')->id());
            });
        }

        // 关键词搜索
        $query->when($request->filled('search'), function ($q) use ($request) {
            $q->where('title', 'like', '%' . $request->search . '%');
        });

        // 分类筛选
        $query->when($request->filled('category_id'), function ($q) use ($request) {
            $q->where('category_id', $request->category_id);
        });

        // 作者筛选（仅管理员或特定场景）
        $query->when($request->filled('user_id') && auth('api')->check(), function ($q) use ($request) {
            $q->where('user_id', $request->user_id);
        });

        // 时间范围筛选
        $query->when($request->filled('start_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->start_date);
        });

        $query->when($request->filled('end_date'), function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->end_date);
        });

        // 排序：支持多种排序方式
        $sortField = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        // 允许排序的字段白名单，防止SQL注入
        $allowedSortFields = ['created_at', 'updated_at', 'title', 'is_public'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // 分页
        $perPage = $request->input('per_page', 15);
        $notes = $query->paginate($perPage);

        return response()->json($notes);
    }
}