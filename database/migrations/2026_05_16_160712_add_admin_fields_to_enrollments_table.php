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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->after('id');
            $table->string('phone', 20)->nullable()->after('student_name');
            $table->string('email')->nullable()->after('phone');
            $table->string('teacher')->nullable()->after('email');
            $table->decimal('attendance_percentage', 5, 2)->nullable()->after('teacher');
            $table->boolean('certificate_eligible')->default(false)->after('attendance_percentage');
            $table->string('payment_status', 30)->default('pending')->after('status');
            $table->string('crm_status', 30)->default('active')->after('payment_status');
            $table->text('notes')->nullable()->after('crm_status');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'lead_id', 'phone', 'email', 'teacher',
                'attendance_percentage', 'certificate_eligible',
                'payment_status', 'crm_status', 'notes',
            ]);
        });
    }
};
