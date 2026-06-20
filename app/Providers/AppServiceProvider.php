<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\EpaperEdition;
use App\Models\Submission;
use App\Models\User;
use App\Models\Video;
use App\Observers\UserObserver;
use App\Policies\ArticlePolicy;
use App\Policies\EpaperEditionPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\VideoPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();

        Paginator::useTailwind();

        User::observe(UserObserver::class);

        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Video::class, VideoPolicy::class);
        Gate::policy(EpaperEdition::class, EpaperEditionPolicy::class);
        Gate::policy(Submission::class, SubmissionPolicy::class);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('og', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        RateLimiter::for('submissions', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
