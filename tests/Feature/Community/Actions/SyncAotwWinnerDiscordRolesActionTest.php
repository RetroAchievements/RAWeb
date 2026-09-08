<?php

declare(strict_types=1);

use App\Community\Actions\SetDiscordMemberRoleAction;
use App\Community\Actions\SyncAotwWinnerDiscordRolesAction;
use App\Http\Actions\FindDiscordMemberAction;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\EventWinnerDiscordRoleGrant;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

use function Pest\Laravel\assertModelMissing;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config([
        'services.discord.rabot_token' => 'token',
        'services.discord.guild_id' => '123',
        'services.discord.aotw_winner_role' => '456',
    ]);
});

function createCurrentAotwWinner(): User
{
    $game = Game::factory()->create(['title' => 'Achievement of the Week']);
    $achievement = Achievement::factory()->promoted()->for($game)->create();
    EventAchievement::factory()->create([
        'achievement_id' => $achievement->id,
        'source_achievement_id' => null,
        'active_from' => now()->subDay(),
        'active_until' => now()->addDay(),
    ]);

    $user = User::factory()->create();
    PlayerAchievement::factory()->hardcore()->create([
        'user_id' => $user->id,
        'achievement_id' => $achievement->id,
    ]);

    return $user;
}

describe('AOTW Discord Roles', function () {
    it('given a current winner, grants the role only once', function () {
        // ARRANGE
        $user = createCurrentAotwWinner();
        $finder = $this->createMock(FindDiscordMemberAction::class);
        $finder->expects($this->once())->method('execute')
            ->with($user->display_name)->willReturn(['user' => ['id' => '100']]);
        $writer = $this->createMock(SetDiscordMemberRoleAction::class);
        $writer->expects($this->once())->method('execute')
            ->with('100', '456', true)->willReturn(true);
        $action = new SyncAotwWinnerDiscordRolesAction($finder, $writer);

        // ACT
        $action->execute();
        $action->execute();

        // ASSERT
        $grant = EventWinnerDiscordRoleGrant::sole();
        expect($grant->user_id)->toBe($user->id);
        expect($grant->discord_user_id)->toBe('100');
    });

    it('given an expired grant, removes the role and its record', function () {
        // ARRANGE
        $grant = EventWinnerDiscordRoleGrant::factory()->create([
            'discord_role_id' => '456',
            'discord_user_id' => '100',
            'expires_at' => now()->subDay(),
        ]);
        $finder = $this->createMock(FindDiscordMemberAction::class);
        $finder->expects($this->never())->method('execute');
        $writer = $this->createMock(SetDiscordMemberRoleAction::class);
        $writer->expects($this->once())->method('execute')
            ->with('100', '456', false)->willReturn(true);

        // ACT
        (new SyncAotwWinnerDiscordRolesAction($finder, $writer))->execute();

        // ASSERT
        assertModelMissing($grant);
    });

    it('given a failed lookup, retries on the next run', function () {
        // ARRANGE
        createCurrentAotwWinner();
        $finder = $this->createMock(FindDiscordMemberAction::class);
        $finder->expects($this->exactly(2))->method('execute')->willReturnOnConsecutiveCalls(
            $this->throwException(new RuntimeException('Discord unavailable')),
            ['user' => ['id' => '100']],
        );
        $writer = $this->createMock(SetDiscordMemberRoleAction::class);
        $writer->expects($this->once())->method('execute')->with('100', '456', true)->willReturn(true);
        $action = new SyncAotwWinnerDiscordRolesAction($finder, $writer);

        // ACT
        $action->execute();
        $action->execute();

        // ASSERT
        expect(EventWinnerDiscordRoleGrant::sole()->discord_user_id)->toBe('100');
    });
});
