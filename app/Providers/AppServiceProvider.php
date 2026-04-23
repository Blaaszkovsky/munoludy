<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
    }

    private function configureRateLimiters(): void
    {
        RateLimiter::for('registration', function (Request $request) {
            if (app()->isLocal()) {
                return Limit::none();
            }

            $email = strtolower(trim((string) $request->input('email', '')));
            $key = $email !== '' ? $request->ip().'|'.$email : $request->ip();

            return [
                Limit::perHour(10)->by($key),
                Limit::perHour(30)->by($request->ip()),
            ];
        });

        RateLimiter::for('vote-code', function (Request $request) {
            if (app()->isLocal()) {
                return Limit::none();
            }

            $hash = (string) $request->route('hash');
            return Limit::perMinutes(5, 5)->by($request->ip().'|'.$hash);
        });
    }
}
