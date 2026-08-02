<?php

declare(strict_types=1);

use App\Community\Services\WatchedTermMatcher;
use App\Models\ModerationWatchlistTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('given a body containing a watched term, it returns that term', function (string $body) {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);

    // Act
    $matches = (new WatchedTermMatcher())->findMatches($body);

    // Assert
    expect($matches)->toEqual(['watchedtool']);
})->with([
    'exact' => 'you should try watchedtool for this',
    'different case' => 'you should try WatchedTool for this',
    'inside a longer word' => 'grab the watchedtoolkit build',
    'inside a URL' => 'see https://example.com/watchedtool/downloads',
    'split by bold tags' => 'grab watched[b][/b]tool here',
    'split by italic tags' => 'grab watched[i][/i]tool here',
    'split by a url tag' => 'grab watched[url=https://example.com][/url]tool here',
]);

it('given a body with no watched term, it returns nothing', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);

    // Act
    $matches = (new WatchedTermMatcher())->findMatches('great set, thanks for the work');

    // Assert
    expect($matches)->toEqual([]);
});

it('given a body matching one term of several watched, it returns just that term as a list', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'firsttool']);
    ModerationWatchlistTerm::create(['term' => 'secondtool']);
    ModerationWatchlistTerm::create(['term' => 'thirdtool']);

    // Act
    $matches = (new WatchedTermMatcher())->findMatches('only thirdtool appears here');

    // Assert
    expect($matches)->toEqual(['thirdtool']);
    expect(array_keys($matches))->toEqual([0]);
});

it('given a body containing three watched terms, it returns all three', function () {
    // Arrange
    ModerationWatchlistTerm::create(['term' => 'watchedtool']);
    ModerationWatchlistTerm::create(['term' => 'secondtool']);
    ModerationWatchlistTerm::create(['term' => 'thirdtool']);

    // Act
    $matches = (new WatchedTermMatcher())->findMatches('watchedtool and SecondTool and thirdtool all work');

    // Assert
    expect($matches)->toEqualCanonicalizing(['watchedtool', 'secondtool', 'thirdtool']);
});
