<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('attachment_url')->nullable()->after('text');
            $table->string('attachment_type')->nullable()->after('attachment_url'); // 'image' or 'file'
            // Make text nullable because you might send JUST an image
            $table->text('text')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_url', 'attachment_type']);
        });
    }
};