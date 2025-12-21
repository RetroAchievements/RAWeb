<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Alerts\Jobs;

use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\SuspiciousBeatTimeAlert;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendAlertWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    private MockHandler $mockHandler;
    private array $webhookHistory = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Set up a mock Guzzle client that we'll use to capture webhook payloads.
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        $history = Middleware::history($this->webhookHistory);
        $handlerStack->push($history);

        $this->mockClient = new Client(['handler' => $handlerStack]);
    }

    private Client $mockClient;

    public function testHandleSendsPostRequestToWebhook(): void
    {
        // Arrange
        $this->mockHandler->append(new Response(200));

        $system = System::factory()->create();
        $user = User::factory()->create(['display_name' => 'Scott']);
        $game = Game::factory()->create(['Title' => 'Sonic', 'ConsoleID' => $system->id]);

        $alert = new SuspiciousBeatTimeAlert(
            user: $user,
            game: $game,
            timeToBeatSeconds: 60,
            medianTimeToBeatSeconds: 3600,
        );

        $job = new SendAlertWebhookJob($alert, 'https://discord.com/api/webhooks/test');
        $job->client = $this->mockClient;

        // Act
        $job->handle();

        // Assert
        $this->assertCount(1, $this->webhookHistory);

        $request = $this->webhookHistory[0]['request'];
        $this->assertEquals('https://discord.com/api/webhooks/test', (string) $request->getUri());
        $this->assertEquals('POST', $request->getMethod());

        $payload = json_decode($request->getBody()->getContents(), true);
        $this->assertArrayHasKey('content', $payload);
        $this->assertStringContainsString('Scott', $payload['content']);
        $this->assertStringContainsString('Sonic', $payload['content']);
    }
}
