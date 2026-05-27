<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_name', 100);
            $table->text('description')->nullable();
            $table->integer('duration')->nullable();
            $table->string('duration_type')->nullable();
            $table->string('delivery_mode')->nullable();
            $table->enum('delivery', ['online', 'offline', 'hybrid']);
            $table->json('support')->nullable();
            $table->json('instruction')->nullable();
            $table->json('schedule')->nullable();
            $table->json('features')->nullable();
            $table->status();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->integer('module_no');
            $table->string('title');
            $table->text('description')->nullable();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('batch_type', 80);
            $table->integer('min_size')->nullable();
            $table->integer('max_size')->nullable();
            $table->decimal('price_npr', 10, 2)->nullable();
            $table->boolean('is_price_variable')->default(false);
            $table->string('offer_label', 100)->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->date('offer_starts_at')->nullable();
            $table->date('offer_ends_at')->nullable();
            $table->text('schedule_notes')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('class_time')->nullable();
            $table->string('class_link')->nullable();
            $table->boolean('is_active')->default(false);
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('student_name', 255);
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('teacher', 255)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('status', 30)->nullable();
            $table->string('payment_status', 30)->default('pending');
            $table->string('crm_status', 30)->default('active');
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->boolean('certificate_eligible')->default(false);
            $table->text('notes')->nullable();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
    }
};
