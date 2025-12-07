<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     */
    protected $middleware = [
        // ... existing middleware
        \App\Http\Middleware\EnsureOrganizationExists::class,
    ];

    /**
     * The application's route middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            // ... existing middleware
            \App\Http\Middleware\CheckUserStatus::class,
            \App\Http\Middleware\EnforceMultiFactorAuth::class,
            \App\Http\Middleware\LogUserActivity::class,
        ],

        'api' => [
            // ... existing middleware
        ],
    ];

    /**
     * The application's route middleware.
     */
    protected $routeMiddleware = [
        // ... existing middleware
        'organization.user' => \App\Http\Middleware\EnsureUserBelongsToOrganization::class,
        'permissions' => \App\Http\Middleware\CheckUserPermissions::class,
        'mfa' => \App\Http\Middleware\EnforceMultiFactorAuth::class,
        'user.status' => \App\Http\Middleware\CheckUserStatus::class,
    ];
}