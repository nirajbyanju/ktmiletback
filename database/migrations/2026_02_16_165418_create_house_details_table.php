<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_details', function (Blueprint $table) {
            $table->id();

            /*
            |----------------------------------------------------------------------
            | Property Relationship (1:1)
            |----------------------------------------------------------------------
            | Keep as unsignedBigInteger, no foreign key
            */
            $table->unsignedBigInteger('property_id')->nullable()->unique();
            $table->unsignedBigInteger('furnishing_id')->nullable();
            $table->unsignedBigInteger('house_type_id')->nullable();

            $table->string('built_area')->nullable(); // Total property area
            $table->unsignedBigInteger('built_area_unit_id')->nullable(); // Total property area unit


            $table->float('total_floors')->nullable();
            $table->json('floor_details')->nullable();

            $table->string('year_built')->nullable();
            $table->string('year_renovated')->nullable();

            $table->unsignedBigInteger('construction_status')->nullable();
            $table->json('construction_status_details')->nullable();
            $table->unsignedBigInteger('roof_type_id')->nullable();

            $table->string('reserved_tank')->nullable();
            $table->string('tank_area')->nullable();

            $table->integer('parking_cars')->nullable();
            $table->integer('parking_bikes')->nullable();
            $table->unsignedBigInteger('parking_type_id', )->nullable();

            $table->decimal('parking_area', 8, 2)->nullable();
            $table->unsignedBigInteger('parking_area_unit_id')->nullable();
            
            $table->json('amenities')->nullable();


            /*
            |----------------------------------------------------------------------
            | Building Face Direction
            |----------------------------------------------------------------------
            */
            $table->unsignedBigInteger('building_face_id')->nullable();

            /*
            |----------------------------------------------------------------------
            | Custom Traits
            |----------------------------------------------------------------------
            */
            $table->userAuditable();

            $table->timestamps();
            $table->softDeletes();

            /*
            |----------------------------------------------------------------------
            | Indexes (optional for performance)
            |----------------------------------------------------------------------
            */
            $table->index('property_id');
            $table->index('building_face_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_details');
    }
};
