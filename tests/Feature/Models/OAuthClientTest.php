<?php

declare(strict_types=1);

use App\Models\OAuthClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('given a client is created, assigns a unique numeric key', function () {
    // Arrange
    $firstClient = OAuthClient::factory()->create();
    $secondClient = OAuthClient::factory()->create();

    // Assert
    expect($firstClient->numeric_id)->not->toBeNull();
    expect($secondClient->numeric_id)->not->toBeNull();
    expect($firstClient->numeric_id)->not->toEqual($secondClient->numeric_id);
});

it('given a client is created, writes audit rows keyed by the numeric key', function () {
    // Arrange
    $client = OAuthClient::factory()->create();

    // Assert
    $auditRow = $client->auditLog()->sole();
    expect($auditRow->event)->toEqual('created');
    expect((int) $auditRow->subject_id)->toEqual($client->numeric_id);
});
