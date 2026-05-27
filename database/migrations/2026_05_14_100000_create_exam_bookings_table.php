<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
         Schema::create('exam_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('exam_name')->nullable();
            $table->string('exam_type');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
         });

        Schema::create('exam_bookings_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_booking_id')->constrained('exam_bookings')->cascadeOnDelete();
            $table->date('preferred_date');
            $table->time('preferred_time')->nullable();
            $table->string('test_location')->nullable();
            $table->string('preferred_test_centre')->nullable();
            $table->string('passport_name');
            $table->string('passport_id')->nullable();
            $table->string('passport_number');
            $table->date('date_of_birth');
            $table->string('contact_number');
            $table->string('phone', 20)->nullable();
            $table->string('email');
            $table->string('passport_copy_path')->nullable();
            $table->string('passport_copy_original_name')->nullable();
            $table->text('special_message')->nullable();
            $table->string('status', 30)->default('new_request');
            $table->boolean('available_slot_checked')->default(false);
            $table->text('admin_notes')->nullable();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_bookings');
    }
};
