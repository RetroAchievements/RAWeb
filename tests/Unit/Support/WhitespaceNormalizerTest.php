<?php

declare(strict_types=1);

use App\Support\WhitespaceNormalizer;

it('normalizes stray whitespace', function (?string $value, ?string $expected) {
    // ACT
    $result = WhitespaceNormalizer::normalize($value);

    // ASSERT
    expect($result)->toEqual($expected);
})->with([
    'null stays null rather than becoming an empty string' => [null, null],
    'no unusual whitespace' => ['Beam Software', 'Beam Software'],

    'non-breaking space' => ["Beam\u{00A0}Software", 'Beam Software'],
    'narrow non-breaking space' => ["Beam\u{202F}Software", 'Beam Software'],
    'thin space' => ["Beam\u{2005}Software", 'Beam Software'],
    'em space' => ["Beam\u{2003}Software", 'Beam Software'],
    'tab' => ["Beam\tSoftware", 'Beam Software'],
    'newline' => ["Beam\nSoftware", 'Beam Software'],
    'carriage return and newline' => ["Beam\r\nSoftware", 'Beam Software'],
]);

it('preserves meaningful characters', function (string $value) {
    // ACT
    $result = WhitespaceNormalizer::normalize($value);

    // ASSERT
    expect($result)->toEqual($value);
})->with([
    'zero-width joiner binding an emoji sequence' => ["\u{1F431}\u{200D}\u{1F464} Top Ninja"],
    'zero-width non-joiner' => ["Hot P\u{200C}ursuit"],
    'ideographic space' => ["モンテカルロ\u{3000}スロットマシーン"],
]);

it('detects invisible whitespace', function (string $value, bool $expected) {
    // ACT
    $result = WhitespaceNormalizer::hasInvisible($value);

    // ASSERT
    expect($result)->toEqual($expected);
})->with([
    'non-breaking space' => ["Beam\u{00A0}Software", true],
    'narrow non-breaking space' => ["Beam\u{202F}Software", true],
    'zero-width space' => ["Beam\u{200B}Software", true],
    'tab' => ["Beam\tSoftware", true],
    'newline' => ["Beam\nSoftware", true],
    'byte order mark' => ["Beam Software\u{FEFF}", true],
]);
