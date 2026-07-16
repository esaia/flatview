<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('content_blocks')->nullable()->after('hero_image');
            $table->dropColumn(['intro', 'features']);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('content_blocks');
            $table->text('intro')->nullable();
            $table->json('features')->nullable();
        });
    }
};
