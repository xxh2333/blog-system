<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\User;

class Note extends Model
{
    use HasFactory;

    // 批量赋值字段
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'content',
        'is_public',
    ];

    // 关联分类模型
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // 关联用户模型
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}