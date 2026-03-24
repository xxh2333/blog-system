<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    // 控制器命名空间（Laravel 10 必需）
    protected $namespace = 'App\Http\Controllers';

    public function boot()
    {
        parent::boot();
    }

    // 定义路由加载规则（核心兼容逻辑）
    public function map()
    {
        // 加载 API 路由（自动加 /api 前缀）
        $this->mapApiRoutes();
        // 加载 Web 路由
        $this->mapWebRoutes();
    }

    // API 路由加载（兼容所有版本）
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace . '\Api') // 匹配 Api 文件夹
            ->group(base_path('routes/api.php'));
    }

    // Web 路由加载
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }
}