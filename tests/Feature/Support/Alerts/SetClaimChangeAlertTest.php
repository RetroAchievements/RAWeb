<?php

namespace Tests\Feature\Support\Alerts;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\SetClaimChangeAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SetClaimChangeAlertTest extends TestCase
{
    use RefreshDatabase;

    private function prepareVariables(ClaimType $claimType, ClaimSetType $setType): array
    {
        $system = System::factory()->create();
        $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);

        $claim = AchievementSetClaim::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'claim_type' => $claimType,
            'set_type' => $setType,
            'status' => ClaimStatus::Active,
            'extensions_count' => 0,
            'special_type' => ClaimSpecial::None,
        ]);

        return [$game, $claim, $user];
    }

    public function testToDiscordMessageNormalClaimPrimaryNotRevision(): void
    {
        // Arrange
        [$game, $claim, $user] = $this->prepareVariables(ClaimType::Primary, ClaimSetType::NewSet);

        $alert = (new SetClaimChangeAlert(game: $game, claim: $claim, user: $user, action: 'create'));

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString(route('game.show', $game), $message);
        $this->assertStringContainsString(':new:', $message);
        $this->assertStringContainsString('Primary claim', $message);
        $this->assertStringContainsString('created', $message);
        $this->assertStringContainsString('Scott', $message);
    }

    public function testToDiscordMessageExtendClaimPrimaryRevision(): void
    {
        // Arrange
        [$game, $claim, $user] = $this->prepareVariables(ClaimType::Primary, ClaimSetType::Revision);

        $alert = (new SetClaimChangeAlert(game: $game, claim: $claim, user: $user, action: 'extend'));

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString(route('game.show', $game), $message);
        $this->assertStringContainsString(':timer:', $message);
        $this->assertStringContainsString('Primary revision claim', $message);
        $this->assertStringContainsString('extended', $message);
        $this->assertStringContainsString('Scott', $message);
    }

    public function testToDiscordMessageDropNonPrimaryNonRevision(): void
    {
        // Arrange
        [$game, $claim, $user] = $this->prepareVariables(ClaimType::Collaboration, ClaimSetType::NewSet);

        $alert = (new SetClaimChangeAlert(game: $game, claim: $claim, user: $user, action: 'drop'));

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString(route('game.show', $game), $message);
        $this->assertStringContainsString(':no_entry_sign:', $message);
        $this->assertStringContainsString('Collaboration claim', $message);
        $this->assertStringContainsString('dropped', $message);
        $this->assertStringContainsString('Scott', $message);
    }

    public function testToDiscordMessageUpdateNonPrimaryRevision(): void
    {
        // Arrange
        [$game, $claim, $user] = $this->prepareVariables(ClaimType::Collaboration, ClaimSetType::Revision);

        $alert = (new SetClaimChangeAlert(game: $game, claim: $claim, user: $user, action: 'update'));

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString(route('game.show', $game), $message);
        $this->assertStringContainsString(':white_check_mark:', $message);
        $this->assertStringContainsString('Collaboration revision claim', $message);
        $this->assertStringContainsString('completed', $message);
        $this->assertStringContainsString('Scott', $message);
    }

    public function testSendDispatchesJobWhenWebhookUrlExists(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.set_claim_change' => 'https://discord.com/api/webhooks/test']);

        [$game, $claim, $user] = $this->prepareVariables(ClaimType::Primary, ClaimSetType::NewSet);

        $alert = (new SetClaimChangeAlert(game: $game, claim: $claim, user: $user, action: 'create'));

        // Act
        $result = $alert->send();

        // Assert
        $this->assertTrue($result);

        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });
    }
}
