<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('maker_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Links to User
            $table->string('field')->nullable();       // e.g. "Woodworking"
            $table->integer('experience')->nullable(); // e.g. "5" years
            $table->text('portfolio')->nullable();     // For future use
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maker_profiles');
    }
};