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
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'video')) {
                $table->string('video')->nullable()->after('image');
            }
            if (!Schema::hasColumn('projects', 'media_type')) {
                $table->string('media_type')->default('image')->after('video');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['video', 'media_type']);
        });
    }
};
