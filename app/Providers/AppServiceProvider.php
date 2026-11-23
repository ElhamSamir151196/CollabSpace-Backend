<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureModels();
        $this->configureResources();
        $this->configurePasswords();
        $this->configureRateLimiting();
    }

    /**
     * Outside production, fail loudly on N+1 queries (lazy loading),
     * on non-fillable attributes and on accessing missing attributes.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * ApiResponse already wraps everything in "data", so resources must not wrap again.
     */
    private function configureResources(): void
    {
        JsonResource::withoutWrapping();
    }

    /**
     * Password rules used everywhere with Password::defaults().
     * SRS: minimum eight characters.
     */
    private function configurePasswords(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8);

            return $this->app->isProduction()
                ? $rule->letters()->numbers()->uncompromised()
                : $rule;
        });
    }

    private function configureRateLimiting(): void
    {
        // General API traffic.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login, register, forgot/reset password — protects against brute force.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // AI calls cost money — limit per user.
        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
