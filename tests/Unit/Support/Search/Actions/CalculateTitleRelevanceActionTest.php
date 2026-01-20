<?php

declare(strict_types=1);

use App\Support\Search\Actions\CalculateTitleRelevanceAction;

it('returns 1.0 for an exact match', function () {
    $action = new CalculateTitleRelevanceAction();

    $relevance = $action->execute('Super Mario Bros.', 'Super Mario Bros.');

    expect($relevance)->toEqual(1.0);
});

it('returns 1.0 for an exact match regardless of case', function () {
    $action = new CalculateTitleRelevanceAction();

    $relevance = $action->execute('super mario bros.', 'Super Mario Bros.');

    expect($relevance)->toEqual(1.0);
});

it('returns at least 0.7 when the title contains the exact query substring', function () {
    $action = new CalculateTitleRelevanceAction();

    $relevance = $action->execute('Mario', 'Super Mario Bros.');

    expect($relevance)->toBeGreaterThanOrEqual(0.7);
});

it('returns a score based on Levenshtein distance for similar strings', function () {
    $action = new CalculateTitleRelevanceAction();

    $relevance = $action->execute('Mario', 'Marig'); // one character difference, so 1 - (1/5) = 0.8

    expect($relevance)->toEqual(0.8);
});

it('returns a low score for completely different strings', function () {
    $action = new CalculateTitleRelevanceAction();

    $relevance = $action->execute('AAAA', 'ZZZZZZZZ');

    expect($relevance)->toBeLessThan(0.5);
});

it('never returns a negative score', function () {
    $action = new CalculateTitleRelevanceAction();

    $relevance = $action->execute('A', 'ZZZZZZZZZZZZZZZZ');

    expect($relevance)->toBeGreaterThanOrEqual(0.0);
});
