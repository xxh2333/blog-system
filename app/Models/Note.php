<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}