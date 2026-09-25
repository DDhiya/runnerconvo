<?php

namespace App\Support;

/**
 * Makes user-typed text safe to drop into a Markdown mail template. Blade's {{ }} and the mail
 * renderer already neutralise HTML, but not Markdown itself: a name like "[Pay here](https://x)"
 * would otherwise render as a live link in the team inbox, and a "|" would break a table row.
 */
class MarkdownText
{
    public static function escape(?string $value): string
    {
        // One line: table cells cannot hold line breaks.
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|<>~])/', '\\\\$1', $value) ?? '';
    }
}
