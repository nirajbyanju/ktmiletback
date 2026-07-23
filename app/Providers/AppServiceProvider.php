<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope is a local debugging tool only. It is a dev-only composer
        // package, so production (`composer install --no-dev`) doesn't ship it —
        // this guard keeps the app booting there and off in any non-local env.
        if ($this->app->environment('local')
            && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('userAuditable', function () {
            /** @var Blueprint $this */
            $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Blueprint::macro('status', function () {
            /** @var Blueprint $this */
            $this->tinyInteger('is_status')->default(0)->comment('0=draft,1=active,2=archived');
            $this->timestamp('published_at')->nullable();
        });

        Blueprint::macro('verified', function () {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $this->timestamp('verified_at')->nullable();
        });
    }
}
