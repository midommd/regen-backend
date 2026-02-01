<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('is_edited')->default(false)->after('is_read');
            $table->boolean('deleted_everyone')->default(false)->after('is_edited');
            $table->json('deleted_for')->nullable()->after('deleted_everyone'); // Stores IDs of users who deleted for themselves
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['is_edited', 'deleted_everyone', 'deleted_for']);
        });
    }
};