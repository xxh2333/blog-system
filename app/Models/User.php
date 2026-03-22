<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 补充 Laravel 必备的工厂 trait
use Tymon\JWTAuth\Contracts\JWTSubject; // 引入 JWT 核心接口

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable; // 补充 HasFactory，避免 tinker 操作报错

    /**
     * 实现 JWTSubject 接口：获取用户唯一标识（必须）
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // 返回用户主键 id，JWT 以此识别用户
    }

    /**
     * 实现 JWTSubject 接口：自定义 JWT 载荷数据（可选，这里返回空数组）
     */
    public function getJWTCustomClaims()
    {
        return []; // 可在这里添加自定义字段，比如 ['role' => $this->role]
    }

    /**
     * 批量赋值白名单（测试用，包含核心字段）
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * 隐藏敏感字段（返回给前端时自动隐藏）
     */
    protected $hidden = [
        'password',
        'remember_token', // 补充 Laravel 默认的记住令牌字段
    ];

    /**
     * 字段类型转换（保证密码哈希、时间字段格式正确）
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Laravel 10+ 推荐的密码哈希转换
        ];
    }


    public function categories()
    {
        return $this->hasMany(Category::class);
    }
}