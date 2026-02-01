<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('projects', function (Blueprint $table) {
        // Allow these to be empty for Link uploads
        $table->string('image_path')->nullable()->change();
        $table->string('material_detected')->nullable()->change();
        $table->json('ai_suggestions')->nullable()->change();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            //
        });
    }
};
