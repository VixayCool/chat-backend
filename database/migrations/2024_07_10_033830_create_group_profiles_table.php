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
        Schema::create('group_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger("group_id")->reference("id")->on("groups");
            $table->string("bio")->nullable();
            $table->string("profile_image")->nullable();
            $table->string("background_image")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_profiles');
    }
};
