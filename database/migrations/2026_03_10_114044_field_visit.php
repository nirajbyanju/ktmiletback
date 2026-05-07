<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('field_visits', function (Blueprint $table) {
            $table->unsignedBigInteger('property_id')->nullable();
            $table->string('date');
            $table->string('time');
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->text('message');
            $table->string('accept_term');
            $table->status();
            $table->text('remarks')->nullable();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_visits');
    }
};
