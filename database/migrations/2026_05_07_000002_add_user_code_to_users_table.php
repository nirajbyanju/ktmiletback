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
        if (!Schema::hasColumn('users', 'userCode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('userCode')->nullable()->after('id');
            });
        }

        DB::table('users')
            ->whereNull('userCode')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($user) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['userCode' => 'SM-2026-'.$user->id]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'userCode')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('userCode');
            });
        }
    }
};
