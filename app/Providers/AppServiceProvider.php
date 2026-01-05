<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Jenssegers\Agent\Agent;

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
        Schema::defaultStringLength(191);

        Blade::if('mobile', function () {
            return (new Agent)->isMobile();
        });

        Blade::if('desktop', function () {
            return (new Agent)->isDesktop();
        });

        // Register Policies
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Account::class, \App\Policies\AccountPolicy::class);

        // Map morph types cho Tags để hỗ trợ cả "product"/"post" và class names
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'product' => \App\Models\Product::class,
            'post' => \App\Models\Post::class,
        ]);
    }
}
