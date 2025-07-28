<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use App\Notifications\PasswordResetEmail;

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
        // Use our custom password reset notification
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            return (new PasswordResetEmail($token))->toMail($notifiable);
        });
    }
}
