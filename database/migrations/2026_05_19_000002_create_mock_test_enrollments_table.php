<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mock_test_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('mock_test_subscriptions')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('enrollment_date');
            $table->date('subscription_start');
            $table->date('subscription_end');
            $table->userAuditable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_test_enrollments');
    }
};
