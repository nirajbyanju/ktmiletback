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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('userAuditable', function () {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Blueprint::macro('status', function () {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->tinyInteger('is_status')->default(0)->comment('0=draft,1=active,2=archived');
            $this->timestamp('published_at')->nullable();
        });

        Blueprint::macro('verified', function () {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->unsignedBigInteger('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $this->timestamp('verified_at')->nullable();
        });
    }
}
