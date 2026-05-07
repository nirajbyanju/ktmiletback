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
            $this->unsignedBigInteger('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->unsignedBigInteger('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->unsignedBigInteger('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $this->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $this->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $this->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });


        Blueprint::macro('status', function () {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->integer('is_status')->default(0)->nullable();
            $this->integer('status')->nullable();
            $this->timestamp('publishedat')->nullable();
        });

        Blueprint::macro('verified', function () {
            /** @var \Illuminate\Database\Schema\Blueprint $this */
            $this->unsignedBigInteger('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreign('verified_by')->references('id')->on('users')->nullOnDelete();

            $this->timestamp('verified_at')->nullable();
        });
    }
}
