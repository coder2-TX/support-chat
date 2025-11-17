<?php

namespace khdija\SupportChat;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class SupportChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // دمج config الباكج مع التطبيق
        $this->mergeConfigFrom(__DIR__.'/config/support_chat.php', 'support_chat');
    }

    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMigrations();

        $this->publishesConfig();
        $this->publishesViews();
        $this->publishesMigrations();
    }

    protected function registerRoutes(): void
    {
        // راوتات السوبر أدمن
        Route::group([
            'middleware' => config('support_chat.admin_middlewares', ['web', 'auth']),
            'prefix'     => config('support_chat.admin_prefix', 'superadmin'),
        ], function () {
            Route::name(config('support_chat.admin_name_prefix', 'admin.'))->group(function () {
                require __DIR__.'/routes/admin.php';
            });
        });

        // راوتات البزنس
        Route::group([
            'middleware' => config('support_chat.business_middlewares', ['web', 'auth']),
            'prefix'     => config('support_chat.business_prefix', ''), // غالباً فاضي
        ], function () {
            Route::name(config('support_chat.business_name_prefix', 'business.'))->group(function () {
                require __DIR__.'/routes/business.php';
            });
        });
    }

    protected function registerViews(): void
    {
        $path = __DIR__.'/resources/views';

        // namespace اختياري: support-chat::...
        $this->loadViewsFrom($path, 'support-chat');

        // إضافة مكان views للـ root (حتى يعمل view('SuperAdmin.support.index'))
        View::addLocation($path);
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function publishesConfig(): void
    {
        $this->publishes([
            __DIR__.'/config/support_chat.php' => config_path('support_chat.php'),
        ], 'support-chat-config');
    }

    protected function publishesViews(): void
    {
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/support-chat'),
        ], 'support-chat-views');
    }

    protected function publishesMigrations(): void
    {
        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'support-chat-migrations');
    }
}
