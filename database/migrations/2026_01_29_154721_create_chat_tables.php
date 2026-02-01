<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ✅ ADD THESE 2 LINES (Clears old tables to prevent error)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::enableForeignKeyConstraints();

        // 1. Conversations Table (The "Room")
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user2_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user1_id', 'user2_id']); 
        });

        // 2. Messages Table (The "Text")
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('text');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};