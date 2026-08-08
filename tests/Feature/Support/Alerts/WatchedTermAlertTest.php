<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Alerts\Jobs\SendAlertWebhookJob;
use App\Support\Alerts\WatchedTermAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('given one matched term and a URL, the message names the author, the term, and links the content', function () {
    // Arrange
    $user = User::factory()->create(['username' => 'Scott', 'display_name' => 'Scott']);
    $alert = new WatchedTermAlert(
        user: $user,
        matchedTerms: ['watchedtool'],
        location: 'a game comment',
        destinationUrl: 'https://retroachievements.org/comment/123',
    );

    // Act
    $message = $alert->toDiscordMessage();

    // Assert
    expect($message)->toContain('posted watched term');
    expect($message)->toContain('`watchedtool`');
    expect($message)->toContain('Scott');
    expect($message)->toContain(route('user.show', ['user' => $user]));
    expect($message)->toContain('https://retroachievements.org/comment/123');
});

it('given several matched terms, the message lists all of them', function () {
    // Arrange
    $alert = new WatchedTermAlert(
        user: User::factory()->create(),
        matchedTerms: ['watchedtool', 'secondtool'],
        location: 'a forum post',
        destinationUrl: 'https://retroachievements.org/forums/post/9',
    );

    // Act
    $message = $alert->toDiscordMessage();

    // Assert
    expect($message)->toContain('posted watched terms');
    expect($message)->toContain('`watchedtool`');
    expect($message)->toContain('`secondtool`');
});

it('given a configured webhook, send dispatches the webhook job onto the alerts queue', function () {
    // Arrange
    Queue::fake();
    config(['services.discord.alerts_webhook.watched_term' => 'https://discord.com/api/webhooks/test']);

    // Act
    $sent = (new WatchedTermAlert(
        user: User::factory()->create(),
        matchedTerms: ['watchedtool'],
        location: 'a game comment',
        destinationUrl: 'https://retroachievements.org/comment/123',
    ))->send();

    // Assert
    expect($sent)->toEqual(true);
    Queue::assertPushedOn('alerts', SendAlertWebhookJob::class);
});
