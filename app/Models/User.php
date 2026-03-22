<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    // 允许批量赋值（无数据库版仅作占位）
    protected $fillable = ['name', 'email', 'password'];

    // JWT必须实现的方法
    public function getJWTIdentifier()
    {
        return $this->id ?? 1; // 无数据库时默认返回1
    }

    public function getJWTCustomClaims()
    {
        return [
            'name' => $this->name ?? 'test',
            'email' => $this->email ?? '2633681826@qq.com'
        ];
    }
}