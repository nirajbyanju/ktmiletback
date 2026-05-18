<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->after('id');
            $table->string('student_name')->nullable()->after('user_id');
            $table->string('phone', 20)->nullable()->after('contact_number');
            $table->time('preferred_time')->nullable()->after('preferred_date');
            $table->string('preferred_test_centre')->nullable()->after('test_location');
            $table->string('payment_status', 20)->default('pending')->after('status');
            $table->boolean('available_slot_checked')->default(false)->after('payment_status');
            $table->boolean('pte_login_details_received')->default(false)->after('available_slot_checked');
            $table->string('pte_username_email')->nullable()->after('pte_login_details_received');
            $table->text('pte_password_notes')->nullable()->after('pte_username_email');
        });
    }

    public function down(): void
    {
        Schema::table('exam_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'lead_id',
                'student_name',
                'phone',
                'preferred_time',
                'preferred_test_centre',
                'payment_status',
                'available_slot_checked',
                'pte_login_details_received',
                'pte_username_email',
                'pte_password_notes',
            ]);
        });
    }
};
