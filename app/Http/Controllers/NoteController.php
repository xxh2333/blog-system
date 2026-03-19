<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Note;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    //获取当前用户所有笔记(关联分类信息)
    public function index()
    {
        //只查询当前用户的所有笔记 , 关联分类信息
        $notes = Note::where('user_id',auth()->id())->with('category')->orderby('create_at','desc')->get();
        return response()->json([
            'code' => 200,
            'data' => $notes,
            'message' => '获取笔记列表成功'
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',//确保分类存在且属于自己
            'title' => 'required|string|max:255',
            'content' => 'required|text',
            'is_public' => 'boolean|nullable',
        ]);
        //关键：自动关联当前登录用户ID
        $note = new Note();
        $note->title = $validated['title'];
        return response()->json([
            'code' => 201,
            'data' => $note,
            'message' => '笔记创建成功'
        ],201);
    }
    //查看单篇笔记
    public function show($id)
    {
        $note = Note::where('user_id',auth()->id())->findOrFail($id);
        return response()->json($note);
    }
    //更新笔记
    public function update(Request $request, $id)
    {
        $note = Note::where('user_id',auth()->id())->findOrFail($id);
        $validated = $request->validate([
            'category_id' => 'exists:categories,id',
            'title' => 'required|string|max:255',
            'content' => 'required|text',
            'is_public' => 'boolean|nullable'
        ]);
        $note->update($validated);
        return response()->json($note);
    }
    //删除笔记
    public function destroy($id)
    {
        $note = Note::where('user_id',auth()->id())->findOrFail($id);
        $note->delete();
        return response()->json(['message' => '笔记删除成功']);
    }
    //特殊接口：切换公开/私有状态（PATCH接口）
    public function switchStatus($id)
    {
        $note = Note::where('user_id',auth()->id())->findOrFail($id);
        //取反：公开变私有，私有变公开
        $note->is_public = !$note->is_public;
        $note->save();
        return response()->json([
            'message' => '状态切换成功',
            'is_public' => $note->is_public
        ]);
    }
}
