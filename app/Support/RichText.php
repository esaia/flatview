<?php

namespace App\Support;

use Illuminate\Support\Collection;

class RichText
{
    /**
     * Turn plain text into HTML, leaving anything that already contains markup
     * alone. Blank lines become paragraphs and single newlines become breaks.
     *
     * Fields that started life as a plain textarea and later became a rich
     * editor still hold raw text in the database until an editor re-saves them,
     * so both the form and the page have to cope with either shape.
     */
    public static function fromPlain(?string $text): ?string
    {
        if (blank($text) || str_contains($text, '<')) {
            return $text;
        }

        return Collection::make(preg_split('/\R{2,}/', trim($text)))
            ->filter(fn (string $paragraph): bool => filled(trim($paragraph)))
            ->map(fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph))).'</p>')
            ->implode('');
    }
}
