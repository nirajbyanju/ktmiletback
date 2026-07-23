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
            $table->string('subscriptions_name');
            $table->string('subscriptions_type');
            $table->string('subscriptions_category');
            $table->string('company_name');
            $table->string('country');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->integer('duration');
            $table->string('duration_type');
              $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });

       
    }

    public function down(): void
    {
        Schema::dropIfExists('mock_test_subscriptions');
    }
};
