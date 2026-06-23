<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'category')) {
                $table->string('category')->nullable()->after('title');
            }
            if (!Schema::hasColumn('projects', 'year')) {
                $table->string('year', 4)->nullable()->after('category');
            }
            if (!Schema::hasColumn('projects', 'image')) {
                $table->string('image')->nullable()->after('year');
            }
            if (!Schema::hasColumn('projects', 'background_color')) {
                $table->string('background_color', 20)->default('#1a1a1a')->after('image');
            }
            if (!Schema::hasColumn('projects', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('background_color');
            }
            if (!Schema::hasColumn('projects', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['category', 'year', 'image', 'background_color', 'is_active', 'sort_order']);
        });
    }
};
