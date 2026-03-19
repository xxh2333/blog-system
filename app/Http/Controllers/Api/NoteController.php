<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // 引入Auth门面，获取当前登录用户

class NoteController extends Controller
{
    // 【构造方法】给控制器所有方法添加auth:api中间件
    // 除了登录/注册，所有笔记接口都需要登录验证，此处统一添加更高效
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    // 接口1：获取广场公开笔记 + 模糊搜索 + 分页
    // 请求方式：GET
    // 请求地址：/api/notes/public
    // URL参数：?title=关键词&content=关键词（可选，支持模糊搜索）
    public function publicNotes(Request $request)
    {
        // 【Eloquent查询构造】when条件：有参数才执行搜索，无参数则查所有公开笔记
        $notes = Note::where('is_public', 1) // 仅查is_public=1的公开笔记
        ->when($request->title, function ($query, $title) {
            // 模糊搜索标题：like %关键词%
            $query->where('title', 'like', "%{$title}%");
        })
            ->when($request->input('content'), function ($query, $content) {
                // 模糊搜索内容：like %关键词%
                $query->where('content', 'like', "%{$content}%");
            })
            ->with(['user:id,email', 'category:id,name']) // 关联查询：只获取用户/分类的核心字段（避免冗余）
            ->orderBy('created_at', 'desc') // 按创建时间倒序
            ->paginate(10); // 【核心】分页：每页10条，Laravel自动返回分页参数（current_page/total/last_page等）

        // 返回JSON响应：状态码200（成功），包含数据
        return response()->json([
            'code' => 200,
            'message' => '获取广场公开笔记成功',
            'data' => $notes
        ]);
    }

    // 接口2：获取个人笔记 + 模糊搜索 + 分页
    // 请求方式：GET
    // 请求地址：/api/my-notes
    // URL参数：?title=关键词&category_id=分类ID（可选，支持多条件搜索）
    public function myNotes(Request $request)
    {
        // 在每个方法里，把 $user = Auth::user(); 改成：
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // 获取当前登录用户信息（auth:api验证后才有）
        $notes = Note::where('user_id', $user->id) // 【核心】数据隔离：仅查当前用户的笔记
        ->when($request->title, function ($query, $title) {
            $query->where('title', 'like', "%{$title}%");
        })
            ->when($request->category_id, function ($query, $cid) {
                // 按分类筛选：仅查指定分类的笔记
                $query->where('category_id', $cid);
            })
            ->with('category:id,name') // 关联查询分类信息
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'code' => 200,
            'message' => '获取个人笔记成功',
            'data' => $notes
        ]);
    }

    // 接口3：创建笔记（CRUD-增）
    // 请求方式：POST
    // 请求地址：/api/notes
    // 请求体（JSON）：{"category_id":1, "title":"笔记标题", "content":"笔记内容", "is_public":0}
    public function store(Request $request)
    {
        $user = Auth::user();
        // 【核心】创建笔记：自动关联当前用户ID，无需前端传user_id（防止恶意篡改）
        $note = Note::create([
            'user_id' => $user->id, // 从Auth获取，保证数据归属正确
            'category_id' => $request->category_id,
            'title' => $request->title,
            'content' =>$request->input('content'),
            'is_public' => $request->is_public ?? 0 // 默认值0（私有），前端不传则使用默认
        ]);

        return response()->json([
            'code' => 201,
            'message' => '笔记创建成功',
            'data' => $note
        ], 201); // 201状态码：创建资源成功
    }

    // 接口4：获取单条笔记详情（CRUD-查）
    // 请求方式：GET
    // 请求地址：/api/notes/{id}（{id}为笔记ID）
    public function show(Note $note)
    {
        $user = Auth::user();
        // 【核心】权限校验：仅允许笔记所属用户查看详情
        if ($note->user_id != $user->id) {
            return response()->json([
                'code' => 403,
                'message' => '无权限查看该笔记'
            ], 403); // 403状态码：禁止访问
        }

        // 关联查询分类信息
        $note->load('category:id,name');
        return response()->json([
            'code' => 200,
            'message' => '获取笔记详情成功',
            'data' => $note
        ]);
    }

    // 接口5：编辑更新笔记（CRUD-改）
    // 请求方式：PUT/PATCH
    // 请求地址：/api/notes/{id}
    // 请求体（JSON）：{"category_id":2, "title":"修改后的标题", "content":"修改后的内容", "is_public":1}
    public function update(Request $request, Note $note)
    {
        $user = Auth::user();
        // 权限校验：仅允许笔记所属用户编辑
        if ($note->user_id != $user->id) {
            return response()->json([
                'code' => 403,
                'message' => '无权限编辑该笔记'
            ], 403);
        }

        // 更新笔记：仅更新前端传过来的字段，未传字段保持不变
        $note->update($request->only(['category_id', 'title', 'content', 'is_public']));
        return response()->json([
            'code' => 200,
            'message' => '笔记更新成功',
            'data' => $note
        ]);
    }

    // 接口6：删除笔记（CRUD-删）
    // 请求方式：DELETE
    // 请求地址：/api/notes/{id}
    public function destroy(Note $note)
    {
        $user = Auth::user();
        // 权限校验：仅允许笔记所属用户删除
        if ($note->user_id != $user->id) {
            return response()->json([
                'code' => 403,
                'message' => '无权限删除该笔记'
            ], 403);
        }

        $note->delete(); // 删除笔记
        return response()->json([
            'code' => 200,
            'message' => '笔记删除成功'
        ]);
    }

    // 接口7：切换笔记公开状态（核心需求）
    // 请求方式：PATCH
    // 请求地址：/api/notes/{id}/status
    public function toggleStatus(Note $note)
    {
        $user = Auth::user();
        // 权限校验：仅允许笔记所属用户切换状态
        if ($note->user_id != $user->id) {
            return response()->json([
                'code' => 403,
                'message' => '无权限切换该笔记状态'
            ], 403);
        }

        // 【核心】状态切换：取反当前is_public值（0→1，1→0）
        $note->update([
            'is_public' => !$note->is_public
        ]);

        return response()->json([
            'code' => 200,
            'message' => '笔记状态切换成功',
            'data' => [
                'id' => $note->id,
                'is_public' => $note->is_public,
                'status_text' => $note->is_public ? '公共' : '私有' // 前端友好的状态文本
            ]
        ]);
    }
}