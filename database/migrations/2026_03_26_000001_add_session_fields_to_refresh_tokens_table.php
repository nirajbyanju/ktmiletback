<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->foreignId('personal_access_token_id')
                ->nullable()
                ->after('user_id')
                ->constrained('personal_access_tokens')
                ->nullOnDelete();
            $table->string('device_name')->nullable()->after('token');
            $table->string('ip_address', 45)->nullable()->after('device_name');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->timestamp('last_used_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_access_token_id');
            $table->dropColumn([
                'device_name',
                'ip_address',
                'user_agent',
                'last_used_at',
            ]);
        });
    }
};
