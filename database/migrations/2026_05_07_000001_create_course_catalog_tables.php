<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
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

            // store extra features
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->integer('module_no'); // 1,2,3,4
            $table->string('title');

            $table->text('description')->nullable();

            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('batch_type', 50);
            $table->integer('min_size')->nullable();
            $table->integer('max_size')->nullable();
            $table->decimal('price_npr', 10, 2)->nullable();
            $table->boolean('is_price_variable')->default(false);
            $table->text('schedule_notes')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('class_time')->nullable();
            $table->timestamps();
        });

        Schema::create('batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->string('batch_name');

            $table->integer('min_size')->nullable();
            $table->integer('max_size')->nullable();

            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);

            // morning / evening
            $table->string('time_slot')->nullable();
            $table->time('class_time')->nullable();

            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });

        Schema::create('student_course_enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();

            // joined from which module
            $table->integer('join_module_no');

            $table->timestamp('enrollment_date');
            $table->timestamp('subscription_start');
            $table->timestamp('subscription_end');

            $table->boolean('is_completed')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('additional_services');
        Schema::dropIfExists('skills_modules');
        Schema::dropIfExists('support_channels');
        Schema::dropIfExists('batches');
        Schema::dropIfExists('courses');
    }
};
