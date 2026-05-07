<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inqury_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users');
            $table->unsignedInteger('contact_method_id'); // phone, email, whatsapp, website,office visit,
            $table->unsignedBigInteger('followup_status_id'); // interested, not interested, call_later, visit_scheduled
            $table->string('message')->nullable();
            $table->dateTime('next_followup_date')->nullable();

            $table->status();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inqury_followups');
    }
};
