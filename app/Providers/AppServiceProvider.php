<?php

namespace App\Providers;

use App\Models\CalendarEvent;
use App\Models\FamilyList;
use App\Models\Note;
use App\Observers\CalendarEventObserver;
use App\Policies\CalendarEventPolicy;
use App\Policies\FamilyListPolicy;
use App\Policies\NotePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);
        Gate::policy(FamilyList::class, FamilyListPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);

        CalendarEvent::observe(CalendarEventObserver::class);

        Route::model('list', FamilyList::class);

        $this->configureRateLimiting();
        $this->configureDefaults();
    }

    /**
     * Configure API rate limiting.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
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
            : null
        );
    }
}
