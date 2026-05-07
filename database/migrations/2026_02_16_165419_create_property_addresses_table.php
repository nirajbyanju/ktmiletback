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
        Schema::create('property_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('province_id')->nullable();
            $table->string('district_id')->nullable();
            $table->string('municipality_id')->nullable();
            $table->string('ward_id')->nullable();
            $table->string('area')->nullable();
            $table->string('postal_code')->nullable();

            $table->text('full_address')->nullable();

            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();

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
        Schema::dropIfExists('property_addresses');
    }
};
