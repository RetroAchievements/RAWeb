<?php

declare(strict_types=1);

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('given a game is created with invisible whitespace, normalizes it on write', function () {
    // ARRANGE
    $system = System::factory()->create();

    // ACT
    $game = Game::factory()->create([
        'system_id' => $system->id,
        'title' => "Shadowrun\u{00A0}Deluxe",
        'developer' => "Beam\u{00A0}Software",
        'publisher' => "Interplay\tEntertainment",
        'genre' => "Card\u{202F}Game",
    ]);

    // ASSERT
    $stored = $game->fresh();
    expect($stored->title)->toEqual('Shadowrun Deluxe');
    expect($stored->developer)->toEqual('Beam Software');
    expect($stored->publisher)->toEqual('Interplay Entertainment');
    expect($stored->genre)->toEqual('Card Game');
});

it('given a game is updated with invisible whitespace, normalizes it on write', function () {
    // ARRANGE
    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id, 'developer' => 'Beam Software']);

    // ACT
    $game->developer = "Beam\u{00A0}Software\u{200B}";
    $game->save();

    // ASSERT
    expect($game->fresh()->developer)->toEqual('Beam Software');
});

it('given an achievement with invisible whitespace, normalizes it on write', function () {
    // ARRANGE
    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id]);

    // ACT
    $achievement = Achievement::factory()->create([
        'game_id' => $game->id,
        'title' => "Master\u{202F}Destiny",
        'description' => "Finish the game without using continues\t (Master Destiny)",
    ]);

    // ASSERT
    $stored = $achievement->fresh();
    expect($stored->title)->toEqual('Master Destiny');
    expect($stored->description)->toEqual('Finish the game without using continues (Master Destiny)');
});

it('given an achievement with smart quotes and invisible whitespace, normalizes both', function () {
    // ARRANGE
    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id]);

    // ACT
    $achievement = Achievement::factory()->create([
        'game_id' => $game->id,
        'title' => "Don\u{2019}t\u{00A0}Look Back",
    ]);

    // ASSERT
    expect($achievement->fresh()->title)->toEqual("Don't Look Back");
});

it('given a leaderboard with invisible whitespace, normalizes it on write', function () {
    // ARRANGE
    $system = System::factory()->create();
    $game = Game::factory()->create(['system_id' => $system->id]);

    // ACT
    $leaderboard = Leaderboard::factory()->create([
        'game_id' => $game->id,
        'title' => "Fastest\u{00A0}Time",
        'description' => "Complete Level 1 collecting\r\n all hidden bonuses",
    ]);

    // ASSERT
    $stored = $leaderboard->fresh();
    expect($stored->title)->toEqual('Fastest Time');
    expect($stored->description)->toEqual('Complete Level 1 collecting all hidden bonuses');
});
