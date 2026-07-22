<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // All communication templates — WhatsApp (manual) + Email (automated/manual).
        // Editable in Admin → Message Templates; automation reads from here.
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('category', 20); // whatsapp | email_auto | email_manual
            $table->string('group_label', 60)->nullable();   // e.g. "Enrolment & Payment"
            $table->string('trigger_label')->nullable();     // human text: "Sends when payment is verified"
            $table->string('automation', 20)->default('manual'); // active | scheduler | manual
            $table->boolean('is_enabled')->default(true);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('cta_text')->nullable();
            $table->string('cta_path')->nullable();          // appended to the site URL
            $table->text('when_to_use')->nullable();
            $table->json('placeholders')->nullable();        // which [Brackets] this template supports
            // Untouched originals so admin can always "Reset to default"
            $table->string('default_subject')->nullable();
            $table->text('default_body')->nullable();
            $table->string('default_cta_text')->nullable();
            $table->timestamps();
        });

        // Every automated email attempt — powers "did the student get it?",
        // per-student history, and duplicate-send protection.
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->string('template_key');
            $table->string('subject')->nullable();
            $table->string('related_type', 40)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 10)->default('sent');   // sent | failed | skipped
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['template_key', 'related_type', 'related_id'], 'email_logs_dedup_idx');
            $table->index('recipient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('message_templates');
    }
};
