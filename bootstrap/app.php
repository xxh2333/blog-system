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
        // 给 API 接口关闭 CSRF 验证（解决 419 错误）
        $middleware->validateCsrfTokens(except: [
            'api/auth/send-code',
            'api/auth/register',
            'api/auth/login',
        ]);

        // 添加 JWT 中间件
        $middleware->alias([
            'jwt.auth' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,
            'jwt.check' => \Tymon\JWTAuth\Http\Middleware\Check::class,
            'jwt.refresh' => \Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
        ]);
        // 添加这个配置 - 处理未认证用户的重定向
        $middleware->redirectGuestsTo(function ( $request) {
            // API 请求返回 null（会触发 401 响应）
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }
            // Web 请求重定向到 login 路由
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 处理认证异常，返回 JSON 而不是重定向
        $exceptions->respond(function ($e) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => '未授权访问'], 401);
            }
        });
    })->create();
