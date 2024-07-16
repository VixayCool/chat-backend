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
        Schema::create('friendships', function(Blueprint $table){
            $table->id();
            $table->unsignedInteger('user_id')->references('id')->on('users');
            $table->unsignedInteger('friend_id')->references('id')->on('users');
            $table->string('status');//need to chenge to enum
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
