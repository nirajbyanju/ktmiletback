<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Group bookings (Friends Private Group): member enrollments link to
        // the paying leader's enrollment and follow its lifecycle.
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('parent_enrollment_id')->nullable()->after('invoice_id')
                ->constrained('enrollments')->nullOnDelete();
        });

        // Marks packages whose booking carries additional group members
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('is_group')->default(false)->after('is_flexible');
        });

        DB::table('packages')
            ->where('name', 'Friends Private Group')
            ->update(['is_group' => true]);
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_enrollment_id');
        });
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('is_group');
        });
    }
};
