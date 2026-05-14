<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('userCode');
            }

            if (!Schema::hasColumn('users', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }

            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('middle_name');
            }

            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('last_name');
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->integer('status')->default(1)->after('google_id');
            }
        });

        DB::table('users')
            ->orderBy('id')
            ->get()
            ->each(function ($user) {
                $name = trim((string) ($user->name ?? 'User'));
                $parts = preg_split('/\s+/', $name) ?: [];
                $firstName = $parts[0] ?? 'User';
                $lastName = count($parts) > 1 ? (string) end($parts) : $firstName;
                $emailLocalPart = Str::before((string) $user->email, '@') ?: 'user';
                $username = Str::slug($emailLocalPart, '') ?: 'user';

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'first_name' => $user->first_name ?: $firstName,
                        'last_name' => $user->last_name ?: $lastName,
                        'username' => $user->username ?: $username.$user->id,
                        'status' => $user->status ?? 1,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['status', 'google_id', 'phone', 'username', 'last_name', 'middle_name', 'first_name'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
