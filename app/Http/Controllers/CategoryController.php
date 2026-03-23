<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    //创建新分类（自动关联当前用户）
    public  function store(Request $request)
    {
        //参数验证
        $validated = $request->validate([
            'name' => 'required|unique:categories|max:255',]);
        //自动关联当前用户ID
        $category = new Category();
        $category->name = $validated['name'];
        $category->user_id = auth()->id();//关联当前登录用户ID（只能创建自己的分类）
        $category->save();//保存到数据库
        return response()->json([
            'code' => 201,
            'data' => $category,
            'message' => '分类创建成功'
        ],201);
    }

    //查询分类（只能查询当前用户的）
    public function index()
    {
        //只查询当前用户的所有分类
        $categories = Category::where('user_id', auth()->id())->get();
        return response()->json([
            'code' => 200,
            'data' => $categories,
            'message' => '获取分类列表成功'
        ]);
    }
    //获取单个分类（仅自己的分类）
    public function show($id)
    {
        //只允许查询自己的分类，不存在则返回404
        $category = Category::where([
            'id' => $id,
            'user_id' => auth()->id()
        ])->firstOrFail();
        return response()->json([
            'code' => 200,
            'data' => $category,
            'message' => '获取分类详情成功'
        ]);
    }
    //更新仅自己的分类
    public function update(Request $request, $id)
    {
        //只允许更新自己的分类
        $category = Category::where([
            'id' => $id,
            'user_id' => auth()->id()
        ])->firstOrFail();
        //参数验证
        $validated = $request->validate([
            'name' => 'required|unique:categories,name,$id|max:255',
        ]);
        //更新分类名称
        $category->name = $validated['name'];
        $category->save();
        return response()->json([
            'code' => 200,
            'data' => $category,
            'message' => '分类更新成功'
        ]);
    }
    public function destroy($id)
    {
        $category = Category::where([
            'id' => $id,
            'user_id' => auth()->id()
        ])->firstOrFail();
        //删除分类
        $category->delete();

        return response()->json([
            'code' => 200,
            'message' => '分类删除成功'
        ]);
    }

}
