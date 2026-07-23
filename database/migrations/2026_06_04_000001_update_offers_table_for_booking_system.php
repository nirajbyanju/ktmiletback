<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->date('start_date')->nullable()->after('description');
            $table->decimal('claim_discount_amount', 10, 2)->default(0)->after('start_date');
            $table->date('valid_date')->nullable()->after('claim_discount_amount');
            $table->string('status', 20)->default('active')->after('valid_date');
        });

        // Migrate existing data from old columns to new ones
        DB::statement("UPDATE offers SET valid_date = DATE(valid_until) WHERE valid_until IS NOT NULL");
        DB::statement("UPDATE offers SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('valid_until');
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dateTime('valid_until')->nullable()->after('title');
            $table->boolean('is_active')->default(true)->after('valid_until');
        });

        DB::statement("UPDATE offers SET valid_until = valid_date, is_active = CASE WHEN status = 'active' THEN 1 ELSE 0 END");

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['description', 'start_date', 'claim_discount_amount', 'valid_date', 'status']);
        });
    }
};
