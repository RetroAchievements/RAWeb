<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Alerts;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Support\Alerts\ClaimWithUnresolvedTicketsAlert;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ClaimWithUnresolvedTicketsAlertTest extends TestCase
{
    use RefreshDatabase;

    public function testToDiscordMessageFormatsCorrectly(): void
    {
        // Arrange
        $system = System::factory()->create();
        $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
        $game = Game::factory()->create(['title' => 'Sonic the Hedgehog', 'system_id' => $system->id]);

        $alert = new ClaimWithUnresolvedTicketsAlert(
            user: $user,
            game: $game,
            ticketCount: 2,
        );

        // Act
        $message = $alert->toDiscordMessage();

        // Assert
        $this->assertStringContainsString('Scott', $message);
        $this->assertStringContainsString('Sonic the Hedgehog', $message);
        $this->assertStringContainsString('created a claim on', $message);
        $this->assertStringContainsString('[2 open tickets]', $message);
        $this->assertStringContainsString(route('user.show', ['user' => $user]), $message);
        $this->assertStringContainsString(route('game.show', ['game' => $game]), $message);
        $this->assertStringContainsString(route('developer.tickets', ['user' => $user->display_name]), $message);
    }

    public function testSendDispatchesJobWhenWebhookUrlExists(): void
    {
        // Arrange
        Queue::fake();

        config(['services.discord.alerts_webhook.claim_with_unresolved_tickets' => 'https://discord.com/api/webhooks/test']);

        $system = System::factory()->create();
        $user = User::factory()->create();
        $game = Game::factory()->create(['system_id' => $system->id]);

        $alert = new ClaimWithUnresolvedTicketsAlert(
            user: $user,
            game: $game,
            ticketCount: 2,
        );

        // Act
        $result = $alert->send();

        // Assert
        $this->assertTrue($result);

        Queue::assertPushedOn('alerts', SendAlertWebhookJob::class, function ($job) {
            return $job->webhookUrl === 'https://discord.com/api/webhooks/test';
        });
    }
}
