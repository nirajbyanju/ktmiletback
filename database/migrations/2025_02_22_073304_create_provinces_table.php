<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_provinces_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_provinces', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->string('slug')->unique();
            $table->string('country_id')->nullable();
            $table->status();
            $table->userAuditable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('provinces');
    }
};
