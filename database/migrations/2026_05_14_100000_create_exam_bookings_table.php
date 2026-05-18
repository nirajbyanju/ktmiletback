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
            $table->string('test_type');
            $table->string('test_type');
            $table->integer('price', 10, 2);
            $table->string('discount', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('exam_bookings_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_booking_id')->constrained('exam_bookings')->cascadeOnDelete();
            $table->date('preferred_date');
            $table->string('test_location');
            $table->string('passport_name');
            $table->string('passport_number');
            $table->date('date_of_birth');
            $table->string('contact_number');
            $table->string('email');
            $table->string('passport_copy_path')->nullable();
            $table->string('passport_copy_original_name')->nullable();
            $table->text('special_message')->nullable();
            $table->string('status', 30)->default('new_request');
            $table->text('admin_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_bookings');
    }
};
