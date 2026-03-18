<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public  function store(Request $request)
    {
        $category = new Category();
        $category->name = $request->input('name'); //接受前端传的分类名
        $category->user_id = auth()->id();//关联当前登录用户ID（只能创建自己的分类）
        $category->save();//保存到数据库
        return response()->json($category);
    }

    //查询分类（只能查询当前用户的）
    public function index()
    {
        //通过user_id过滤，只查自己的分类
        $categories = Category::where('user_id', auth()->id())->get();
        return response()->json($categories);
    }

}
