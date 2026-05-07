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
        Schema::create('blogs_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author')->nullable();
            $table->integer('category_id');
            $table->longText('content');
            $table->datetime('publish_date');
            $table->string('tags')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('media')->nullable();
            $table->integer('status');
            $table->integer('is_status');
            $table->timestamp('scheduled_publish_date')->nullable();

            $table->integer('view_count')->default(1); // Changed to integer
            $table->integer('like_count')->default(0); // Track user interactions
            $table->integer('bookmark_count')->default(0);

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
        Schema::dropIfExists('blogs_posts');
    }
};
