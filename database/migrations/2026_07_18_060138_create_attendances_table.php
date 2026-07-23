<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per student, per class day. A row references EITHER an enrollment
     * (paid course student) OR a demo_request (trial student) — never both.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('demo_request_id')->nullable()->constrained('demo_requests')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->date('attended_on');
            $table->string('status', 20)->default('present'); // present | absent
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // A student can only have one mark per day.
            $table->unique(['enrollment_id', 'attended_on']);
            $table->unique(['demo_request_id', 'attended_on']);
            $table->index(['batch_id', 'attended_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
