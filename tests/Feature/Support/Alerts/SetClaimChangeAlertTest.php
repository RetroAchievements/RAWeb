<?php

namespace Tests\Feature\Support\Alerts;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\SetClaimChangeAction;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\SetClaimChangeAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $system = System::factory()->create();
    $this->user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
    $this->game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);
});

dataset('claim actions', [
    'created' => [SetClaimChangeAction::Create, ':new:', 'created'],
    'extended' => [SetClaimChangeAction::Extend, ':timer:', 'extended'],
    'dropped' => [SetClaimChangeAction::Drop, ':no_entry_sign:', 'dropped'],
    'completed' => [SetClaimChangeAction::Update, ':white_check_mark:', 'completed'],
]);

dataset('set types', [
    'new' => [ClaimSetType::NewSet, ''],
    'revision' => [ClaimSetType::Revision, ' revision'],
]);

dataset('claim types', [
    'primary' => [ClaimType::Primary, 'Primary'],
    'collaboration' => [ClaimType::Collaboration, 'Collaboration'],
]);

it('formats a set claim change discord message', function (
    SetClaimChangeAction $action,
    string $expectedEmoji,
    string $expectedAction,
    ClaimSetType $setType,
    string $expectedRevisionText,
    ClaimType $claimType,
    string $expectedClaimType,
): void {
    // Arrange
    $claim = AchievementSetClaim::create([
        'user_id' => $this->user->id,
        'game_id' => $this->game->id,
        'claim_type' => $claimType,
        'set_type' => $setType,
        'status' => ClaimStatus::Active,
        'extensions_count' => 0,
        'special_type' => ClaimSpecial::None,
    ]);

    $alert = new SetClaimChangeAlert(game: $this->game, user: $this->user, claim: $claim, action: $action);

    // Act
    $message = $alert->toDiscordMessage();

    // Assert
    expect($message)->toContain(route('game.show', $this->game))
        ->and($message)->toContain($expectedEmoji)
        ->and($message)->toContain($expectedClaimType)
        ->and($message)->toContain($expectedAction)
        ->and($message)->toContain('Scott');

    if ($setType === ClaimSetType::Revision) {
        expect($message)->toContain($expectedRevisionText);
    }

})->with('claim actions')
    ->with('set types')
    ->with('claim types');

it('dispatches the alert webhook job when configured', function (): void {
    // Arrange
    Queue::fake();

    config(['services.discord.alerts_webhook.set_claim_change' => 'https://discord.com/api/webhooks/test']);

    $claim = AchievementSetClaim::create([
        'user_id' => $this->user->id,
        'game_id' => $this->game->id,
        'claim_type' => ClaimType::Primary,
        'set_type' => ClaimSetType::NewSet,
        'status' => ClaimStatus::Active,
        'extensions_count' => 0,
        'special_type' => ClaimSpecial::None,
    ]);

    $alert = new SetClaimChangeAlert(game: $this->game, user: $this->user, claim: $claim, action: SetClaimChangeAction::Create);

    // Act
    $result = $alert->send();

    // Assert
    expect($result)->toBeTrue();

    Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
        return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
    });
});
