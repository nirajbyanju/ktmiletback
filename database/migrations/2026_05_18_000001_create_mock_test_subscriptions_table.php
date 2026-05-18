<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_test_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscriptions_type');
            $table->string('subscriptions_category');
            $table->string('subscriptions_name');
            $table->string('company_name');
            $table->string('country');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->integer('duration');
            $table->string('duration_type');
            $table->timestamps();
        });

        Schema::create('mock_test_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('mock_test_subscriptions')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('enrollment_date');
            $table->date('subscription_start');
            $table->date('subscription_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_test_subscriptions');
    }
};
