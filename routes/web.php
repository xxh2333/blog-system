<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//测试邮箱配置是否生效
use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    Mail::raw('这是一封测试邮件', function ($message) {
        $message->to('测试接收邮箱@xxx.com')->subject('Laravel 邮件测试');
    });
    return '邮件发送成功';
});