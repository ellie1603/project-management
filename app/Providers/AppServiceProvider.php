<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Permissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        // The System Administrator (developer) account can do everything. Every
        // other user, the Admin/CEO included, gets exactly what their role's
        // permissions allow (App\Services\Permissions).
        Gate::before(fn ($user) => $user?->isSystemAdmin() ? true : null);

        foreach (array_keys(Permissions::all()) as $permission) {
            Gate::define($permission, fn (User $user): bool => $user->hasPermission($permission));
        }

        Gate::define('manage-system', fn (User $user): bool => $user->isSystemAdmin());

        $this->configureRateLimiting();
    }

    /**
     * Named limiters used by the throttle middleware. The limits themselves
     * live in config/rate_limits.php.
     */
    private function configureRateLimiting(): void
    {
        // Applied to every web request (see bootstrap/app.php).
        RateLimiter::for('global', function (Request $request): array {
            $client = $this->rateLimitClient($request);
            $limits = [
                Limit::perMinute(config($request->user() ? 'rate_limits.global_user' : 'rate_limits.global_guest'))->by($client),
            ];

            if (! $request->isMethodSafe()) {
                $limits[] = Limit::perMinute(config('rate_limits.writes'))->by('write:'.$client);
            }

            return $limits;
        });

        // LoginRequest already locks an email + IP pair after 5 failures; this
        // also caps one IP that keeps switching email addresses.
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(config('rate_limits.login'))->by('ip:'.$request->ip()));

        foreach (['password', 'search', 'reports', 'uploads'] as $name) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute(config("rate_limits.{$name}"))->by($this->rateLimitClient($request)));
        }
    }

    private function rateLimitClient(Request $request): string
    {
        return $request->user()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'ip:'.$request->ip();
    }
}
