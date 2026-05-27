<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('name', 150)->nullable()->after('teacher_id');
            $table->string('phone', 20)->nullable()->after('name');
            $table->string('email', 255)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone', 'email']);
        });
    }
};
