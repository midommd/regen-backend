<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            // 1. Add Category if missing
            if (!Schema::hasColumn('projects', 'category')) {
                $table->string('category')->nullable()->default('General')->after('description');
            }

            // 2. Add Price if missing
            if (!Schema::hasColumn('projects', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('category');
            }

            // 3. Add For Sale Status if missing
            if (!Schema::hasColumn('projects', 'is_for_sale')) {
                $table->boolean('is_for_sale')->default(false)->after('price');
            }
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['category', 'price', 'is_for_sale']);
        });
    }
};