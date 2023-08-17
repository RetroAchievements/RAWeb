<?php

use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Facades\Validator;

function sanitize_outputs(string|int|null &...$outputs): void
{
    /** @var string $output */
    foreach ($outputs as &$output) {
        if (!empty($output)) {
            $output = htmlentities($output, double_encode: false);
        }
    }
}

function attributeEscape(?string $input): string
{
    if (!$input) {
        return '';
    }

    // htmlspecialchars escapes a bunch of stuff that the tooltip can't handle
    // (like &rsquo; $frac12; and &deg;). when placed in title or alt fields.
    // just do the bare minimum.
    $input = str_replace("'", "&#39;", $input);

    return str_replace('"', "&quot;", $input);
}

function isValidUsername(?string $username): bool
{
    if (Str::startsWith($username, '__')) {
        return true;
    }

    // Note: use request validation where applicable instead of checking the username manually
    // Note: allow 2 character usernames for existing accounts. New accounts are limited to 4.
    return Validator::make(
        ['username' => $username],
        ['username' => ['min:2', 'max:20', new CtypeAlnum()]]
    )->passes();
}

function separateList(string $items): array
{
    // replace allowed delimiters with spaces
    $items = str_replace([
        ',',
        ';',
        "\t",
        '|',
    ], ' ', $items);

    // split into array, remove empty values, reset keys
    $items = explode(' ', $items);
    $items = array_filter($items);

    return array_values($items);
}

function utf8_sanitize(string $input): string
{
    return mb_convert_encoding($input, "UTF-8", "UTF-8");
}
