<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The "Why Flatview?" value props moved from the services page to the about
 * page, so their settings are renamed to match where they are now edited.
 */
return new class extends Migration
{
    /** old key => new key */
    private const KEYS = [
        'services_valueprops_kicker' => 'about_valueprops_kicker',
        'services_valueprops' => 'about_valueprops',
    ];

    public function up(): void
    {
        foreach (self::KEYS as $old => $new) {
            $this->rename($old, $new);
        }
    }

    public function down(): void
    {
        foreach (array_flip(self::KEYS) as $old => $new) {
            $this->rename($old, $new);
        }
    }

    /**
     * Rename a setting, keeping whichever row already holds a value so the
     * migration can run on a database where either name is present.
     */
    private function rename(string $old, string $new): void
    {
        $value = DB::table('homepage_settings')->where('key', $old)->value('value');

        if ($value === null) {
            return;
        }

        DB::table('homepage_settings')->where('key', $new)->delete();
        DB::table('homepage_settings')->where('key', $old)->update(['key' => $new]);
    }
};
