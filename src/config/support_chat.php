<?php

return [

    // موديل المنشأة (Business)
    'business_model' => App\Models\Business::class,

    // موديل المستخدم
    'user_model' => App\Models\User::class,

    // ميدل وير السوبر أدمن
    'admin_middlewares' => [
        'web',
        'auth',
        App\Http\Middleware\SuperAdminMiddleware::class,
    ],

    // ميدل وير البزنس
    'business_middlewares' => [
        'web',
        'auth',
        'verified',
        App\Http\Middleware\EnsureBusinessUser::class,
    ],

    // Prefix السوبر أدمن (superadmin مثل طابور)
    'admin_prefix' => 'superadmin',

    // Prefix للبزنس (غالباً فاضي)
    'business_prefix' => '',

    // Prefix لأسماء الراوت
    'admin_name_prefix'    => 'admin.',
    'business_name_prefix' => 'business.',

    // أسماء الواجهات الأم (layout)
    'admin_layout'    => 'layouts.superadmin',
    'business_layout' => 'layouts.app',
];
