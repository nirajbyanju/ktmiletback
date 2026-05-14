<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('offer_label', 100)->nullable()->after('price_npr');
            $table->string('discount_type', 20)->nullable()->after('offer_label');
            $table->decimal('discount_value', 10, 2)->nullable()->after('discount_type');
            $table->date('offer_starts_at')->nullable()->after('discount_value');
            $table->date('offer_ends_at')->nullable()->after('offer_starts_at');
            $table->string('class_link')->nullable()->after('class_time');
            $table->boolean('is_active')->default(true)->after('class_link');
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn([
                'offer_label',
                'discount_type',
                'discount_value',
                'offer_starts_at',
                'offer_ends_at',
                'class_link',
                'is_active',
            ]);
        });
    }
};
