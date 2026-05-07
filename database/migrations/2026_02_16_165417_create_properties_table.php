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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('property_code')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tags');
            $table->longText('description')->nullable();
            $table->string('land_area')->nullable();
            $table->unsignedBigInteger('land_unit_id')->nullable();
            $table->unsignedBigInteger('property_face_id')->nullable();
            $table->unsignedBigInteger('property_type_id')->nullable();
            $table->unsignedBigInteger('listing_type_id')->nullable();
            $table->unsignedBigInteger('property_category_id')->nullable();

            $table->string('length')->nullable();
            $table->string('height')->nullable();
            $table->unsignedBigInteger('measure_unit_id')->nullable();

            $table->boolean('is_road_accessible')->default(true);
            $table->unsignedBigInteger('road_type_id')->nullable();
            $table->unsignedBigInteger('road_condition_id')->nullable();
            $table->string('road_width')->nullable();

            $table->decimal('base_price', 15, 2);
            $table->decimal('advertise_price', 15, 2);
            $table->string('currency', 10)->default('NPR');

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_negotiable')->default(false);
            $table->boolean('banking_available')->default(false);

            $table->boolean('has_electricity')->default(false);
            $table->unsignedBigInteger('water_source_id')->nullable();

            $table->unsignedBigInteger('sewage_type_id')->nullable();

            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();

            $table->unsignedBigInteger('property_status_id')->default(0);

            $table->userAuditable();
            $table->status();

            $table->verified();

            $table->timestamps();
            $table->softDeletes();

            $table->index('base_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
