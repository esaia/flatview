<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public showcase pages for IREP projects: the marketing copy and imagery that
 * wraps an interactive project so it can be shown off from the services page.
 * The interactive data itself still lives in the `projects` tree.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_projects', function (Blueprint $table) {
            $table->id();
            // The IREP project this page presents. Nullable so a page survives
            // the project being deleted (it simply stops rendering the viewer).
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            $table->string('slug')->unique();
            $table->string('title');
            $table->string('tagline')->nullable();

            $table->string('card_image')->nullable();
            $table->string('hero_image')->nullable();
            $table->text('hero_description')->nullable();

            $table->string('plan_kicker')->nullable();
            $table->string('plan_headline')->nullable();
            $table->string('plan_headline_accent')->nullable();
            $table->text('plan_intro')->nullable();

            $table->string('units_kicker')->nullable();
            $table->string('units_headline')->nullable();
            $table->string('units_headline_accent')->nullable();
            $table->text('units_intro')->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_projects');
    }
};
