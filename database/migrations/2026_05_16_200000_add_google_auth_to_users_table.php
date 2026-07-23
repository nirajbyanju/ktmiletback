<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Make password nullable so Google-only accounts work
            $table->string('password')->nullable()->change();

            // Track whether the user has set a password (Google users start without one)
            if (!Schema::hasColumn('users', 'has_password')) {
                $table->boolean('has_password')->default(true)->after('password');
            }
        });

        // Mark existing rows: users with a password hash have one set
        DB::table('users')->whereNotNull('password')->update(['has_password' => true]);
        DB::table('users')->whereNull('password')->update(['has_password' => false]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();

            if (Schema::hasColumn('users', 'has_password')) {
                $table->dropColumn('has_password');
            }
        });
    }
};
