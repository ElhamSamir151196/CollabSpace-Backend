<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\ReportPolicy;
use App\Services\Search\Providers\FileSearch;
use App\Services\Search\Providers\MeetingSearch;
use App\Services\Search\Providers\MessageSearch;
use App\Services\Search\Providers\ProjectSearch;
use App\Services\Search\Providers\TaskSearch;
use App\Services\Search\Providers\TeamSearch;
use App\Services\Search\Providers\UserSearch;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
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
        // Sections of the global search, in the order they are shown.
        $this->app->tag([
            ProjectSearch::class,
            TaskSearch::class,
            MeetingSearch::class,
            FileSearch::class,
            MessageSearch::class,
            TeamSearch::class,
            UserSearch::class,
        ], 'search.providers');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureModels();
        $this->configureResources();
        $this->configurePasswords();
        $this->configurePasswordResetUrl();
        $this->configureRateLimiting();
        $this->configureGates();
    }

    /**
     * Abilities that are not about a single model.
     */
    private function configureGates(): void
    {
        Gate::define('viewReports', [ReportPolicy::class, 'viewReports']);
        Gate::define('viewUserReport', [ReportPolicy::class, 'viewUserReport']);
    }

    /**
     * The reset email links to the React app, not to a Laravel page.
     */
    private function configurePasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            return config('collabspace.frontend_url').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]);
        });
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
