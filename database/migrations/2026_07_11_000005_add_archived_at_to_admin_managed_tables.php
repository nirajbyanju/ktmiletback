<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Admin "delete" = archive: record is hidden from default lists but restorable. */
    private const TABLES = [
        'enrollments',
        'mock_test_enrollments',
        'demo_requests',
        'exam_bookings_enrollments',
        'contact_messages',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->timestamp('archived_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('archived_at');
            });
        }
    }
};
