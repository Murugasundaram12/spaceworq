<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\LayoutTemplate;
use App\Models\Provider;
use App\Models\ReserveRequest;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\BookingPolicy;
use App\Policies\LayoutTemplatePolicy;
use App\Policies\ProviderPolicy;
use App\Policies\ReserveRequestPolicy;
use App\Policies\SupportTicketPolicy;
use App\Policies\WorkspacePolicy;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\RazorpayPaymentGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, RazorpayPaymentGateway::class);
    }

    public function boot(): void
    {
        Gate::policy(Provider::class, ProviderPolicy::class);
        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(ReserveRequest::class, ReserveRequestPolicy::class);
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
        Gate::policy(LayoutTemplate::class, LayoutTemplatePolicy::class);

        RateLimiter::for('auth', function (Request $request) {
            $key = $request->ip() . '|' . strtolower((string) $request->input('identifier', $request->input('email', 'guest')));
            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('otp', function (Request $request) {
            $key = $request->ip() . '|' . strtolower((string) $request->input('identifier', 'guest'));
            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('inquiries', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('booking', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $email = method_exists($notifiable, 'getEmailForPasswordReset')
                ? $notifiable->getEmailForPasswordReset()
                : ($notifiable->email ?? '');

            if ($notifiable instanceof User && $notifiable->user_type === User::TYPE_PROVIDER) {
                return url(route('provider.password.reset', [
                    'token' => $token,
                    'email' => $email,
                ], false));
            }

            return url(route('password.reset', [
                'token' => $token,
                'email' => $email,
            ], false));
        });
    }
}
