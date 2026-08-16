<?php

namespace App\Http\Controllers;

use App\Models\HomepageSetting;

abstract class Controller
{
    /**
     * The shared FAQ, rendered on more than one page and edited in one place
     * (Website → FAQ).
     *
     * @return array{kicker: string, headline: string, intro: string, items: array<int, array{question: string, answer: string}>}
     */
    protected function faq(): array
    {
        $stored = HomepageSetting::where('key', 'like', 'faq_%')
            ->pluck('value', 'key')
            ->toArray();

        $get = fn (string $key, string $default) => filled($stored[$key] ?? null) ? $stored[$key] : $default;

        $items = json_decode($stored['faq_items'] ?? '[]', true);

        return [
            'kicker' => $get('faq_kicker', 'Questions'),
            'headline' => $get('faq_headline', "Answers,\nbefore you ask"),
            'intro' => $get('faq_intro', ''),
            'items' => is_array($items) ? array_values($items) : [],
        ];
    }
}
