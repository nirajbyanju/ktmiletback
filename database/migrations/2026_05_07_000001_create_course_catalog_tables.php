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
            $table->string('name', 100);
            $table->integer('duration_weeks');
            $table->integer('total_hours');
            $table->string('delivery_mode', 50);
            $table->string('instruction_lang', 50);
            $table->text('skills')->nullable();
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

        Schema::create('support_channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_type', 50);
            $table->string('contact_value');
            $table->timestamps();
        });

        Schema::create('skills_modules', function (Blueprint $table) {
            $table->id();
            $table->string('skill_name', 50);
            $table->text('topics_covered')->nullable();
            $table->boolean('feedback_included')->default(false);
            $table->timestamps();
        });

        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_add_on')->default(true);
            $table->decimal('price_npr', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
            $table->date('enrollment_date')->default(DB::raw('CURRENT_DATE'));
            $table->decimal('amount_paid', 10, 2)->nullable();
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
