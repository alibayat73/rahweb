<?php

namespace App\Providers;

use App\Contracts\DeliveryClientInterface;
use App\Infrastructure\Adapters\MockWebserviceAdapter;
use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DeliveryClientInterface::class, MockWebserviceAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configurePolicies();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure policies for authorization.
     */
    protected function configurePolicies(): void
    {
        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });
    }
}
