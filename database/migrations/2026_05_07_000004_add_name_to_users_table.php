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
        if (!Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable()->after('userCode');
            });
        }

        DB::table('users')
            ->whereNull('name')
            ->orderBy('id')
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'email'])
            ->each(function ($user) {
                $name = trim(implode(' ', array_filter([
                    $user->first_name,
                    $user->middle_name,
                    $user->last_name,
                ])));

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['name' => $name !== '' ? $name : $user->email]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};
