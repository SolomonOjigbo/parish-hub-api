<?php

namespace App\Providers;

use App\Models\Member;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Observers\MemberObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

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
        Member::observe(MemberObserver::class);

        // Single audit store: app-level audit_logs (members are logged
        // inline with request context by MemberController).
        foreach ([
            \App\Models\Offering::class,
            \App\Models\Tithe::class,
            \App\Models\Pledge::class,
            \App\Models\PledgePayment::class,
            \App\Models\Donation::class,
            \App\Models\Event::class,
            \App\Models\Family::class,
            \App\Models\Society::class,
            User::class,
        ] as $model) {
            $model::observe(AuditObserver::class);
        }

        // Password reset emails must land on the SPA, not a backend route.
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $frontend = rtrim(config('app.frontend_url'), '/');

            return $frontend . '/reset-password?' . http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]);
        });
    }
}
