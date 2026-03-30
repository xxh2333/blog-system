<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;
    //属于一个用户

    // 批量赋值字段
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'category_id',
        'content',
        'is_public',
    ];
    protected $casts = [
        'is_public' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    // 范围查询：公开笔记
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // 范围查询：私有笔记
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }
}
