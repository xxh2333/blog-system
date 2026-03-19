<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    // 【核心】批量赋值字段：允许通过create/update批量写入的字段
    // 必须包含业务中需要手动赋值的字段，排除主键/时间戳（Laravel自动维护）
    protected $fillable = [
        'user_id',   // 关联用户ID
        'category_id', // 关联分类ID
        'title',     // 笔记标题
        'content',   // 笔记内容
        'is_public'  // 公开状态（0私有/1公共）
    ];

    // 【核心】模型关联：笔记属于一个用户（多对一）
    // 关联users表，外键为user_id，关联模型为User::class
    public function user()
    {
        // onDelete('cascade')：用户删除时，关联的笔记也自动删除（和迁移文件外键一致）
        return $this->belongsTo(User::class)->onDelete('cascade');
    }

    // 【核心】模型关联：笔记属于一个分类（多对一）
    // 关联categories表，外键为category_id，关联模型为Category::class
    public function category()
    {
        return $this->belongsTo(Category::class)->onDelete('cascade');
    }
}