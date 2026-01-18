<?php

declare(strict_types=1);

use App\Filament\GlobalSearch\RelevanceBasedGlobalSearchProvider;
use App\Models\Game;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not crash when searching', function () {
    // ARRANGE
    $provider = app(RelevanceBasedGlobalSearchProvider::class);
    System::factory()->create(['id' => 1, 'name' => 'NES']);
    Game::factory()->create(['title' => 'Super Mario Bros.', 'system_id' => 1]);

    // ACT
    $results = $provider->getResults('Super Mario');

    // ASSERT
    expect($results)->not->toBeNull();
});
