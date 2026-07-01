<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan data auth ke semua halaman Inertia
        // Inertia::share([
        //     'auth' => function () {
        //         return [
        //             'user' => auth()->user(),
        //             'avatar' => auth()->user()?->avatar,
        //             'role' => auth()->user()?->role,
        //         ];
        //     },
        // ]);

        RateLimiter::for('registration', function (Request $request) {
            return [
                // Maks. 5 percobaan per menit per IP
                Limit::perMinute(5)->by($request->ip()),
                // Maks. 10 percobaan per jam per IP  
                Limit::perHour(10)->by('registration_hour_' . $request->ip()),
            ];
        });
        
        // Rate limiter default untuk API admin
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
