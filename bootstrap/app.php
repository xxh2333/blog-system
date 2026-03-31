<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 处理认证异常，返回 JSON 而不是重定向
        $exceptions->respond(function ($e) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => '未授权访问'], 401);
            }
        });
    })->create();
