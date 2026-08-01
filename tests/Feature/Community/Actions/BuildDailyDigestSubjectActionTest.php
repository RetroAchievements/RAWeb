<?php

declare(strict_types=1);

use App\Community\Actions\BuildDailyDigestSubjectAction;
use App\Community\Enums\SubscriptionSubjectType;

function digestItemForSubjectTest(string $type, array $overrides = []): array
{
    return array_merge([
        'type' => $type,
        'title' => 'Sonic the Hedgehog (Genesis/Mega Drive)',
        'gameTitle' => 'Sonic the Hedgehog',
        'link' => 'https://retroachievements.org/game/1',
        'count' => 1,
    ], $overrides);
}

it('given one set release and four comments, the subject names the release and counts four', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('Achievements released for Sonic the Hedgehog, and 4 more updates');
});

it('given three set releases and four comments, then the subject counts six without naming types', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value),
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value, ['title' => 'Chrono Trigger (SNES/Super Famicom)', 'gameTitle' => 'Chrono Trigger']),
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value, ['title' => 'Contra (NES/Famicom)', 'gameTitle' => 'Contra']),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('Achievements released for Sonic the Hedgehog, and 6 more updates');
    expect($subject)->not->toContain('comment');
    expect($subject)->not->toContain('screenshot');
});

it('given a single item, then the subject carries no count', function () {
    // Arrange
    $items = [digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value)];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('Achievements released for Sonic the Hedgehog');
});

it('given exactly one remaining item, then the subject uses the singular form', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('Achievements released for Sonic the Hedgehog, and 1 more update');
});

it('given screenshot decisions and a comment, then the subject leads with the screenshots', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::GameWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameScreenshotDecision->value),
        digestItemForSubjectTest(SubscriptionSubjectType::GameScreenshotDecision->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('Your screenshots were reviewed, and 2 more updates');
});

it('given a set release and a screenshot decision, then the subject leads with the release', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::GameScreenshotDecision->value),
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementSetRelease->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('Achievements released for Sonic the Hedgehog, and 1 more update');
});

it('given one screenshot decision versus an aggregated one, then the subject switches to plural', function () {
    // Arrange
    $single = [digestItemForSubjectTest(SubscriptionSubjectType::GameScreenshotDecision->value)];
    $aggregated = [digestItemForSubjectTest(SubscriptionSubjectType::GameScreenshotDecision->value, ['count' => 3])];

    // Act
    $singleSubject = (new BuildDailyDigestSubjectAction())->execute($single);
    $aggregatedSubject = (new BuildDailyDigestSubjectAction())->execute($aggregated);

    // Assert
    expect($singleSubject)->toEqual('Your screenshot was reviewed');
    expect($aggregatedSubject)->toEqual('Your screenshots were reviewed');
});

it('given a forum topic reply and a leaderboard comment, then the subject leads with the forum topic reply', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::Leaderboard->value),
        digestItemForSubjectTest(SubscriptionSubjectType::ForumTopic->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('New replies to your posts, and 1 more update');
});

it('given ticket activity and a user wall comment, then the subject leads with the ticket activity', function () {
    // Arrange
    $items = [
        digestItemForSubjectTest(SubscriptionSubjectType::UserWall->value),
        digestItemForSubjectTest(SubscriptionSubjectType::AchievementTicket->value),
    ];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('New activity on your tickets, and 1 more update');
});

it('given a subject type we forgot to handle in our code, then the subject falls through to the conversation headline', function () {
    // Arrange
    $items = [digestItemForSubjectTest('SomeTypeAddedLater')];

    // Act
    $subject = (new BuildDailyDigestSubjectAction())->execute($items);

    // Assert
    expect($subject)->toEqual('New comments on things you follow');
});
