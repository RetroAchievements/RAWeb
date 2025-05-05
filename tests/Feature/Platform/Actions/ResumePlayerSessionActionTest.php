<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Models\Achievement;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResumePlayerSessionActionTest extends TestCase
{
    use RefreshDatabase;

    public function testResumePlayerSession(): void
    {
        $sessionStartAt = Carbon::parse('2025-04-01 12:34:56');
        Carbon::setTestNow($sessionStartAt);

        $user = $this->seedUser();
        $game = $this->seedGame(achievements: 3);
        $gameHash = $game->hashes->first();
        $coreAchievementSet = $game->achievementSets()->where('type', AchievementSetType::Core)->first();
        $coreAchievementSet->achievements_published_at = $sessionStartAt->clone()->subDays(5);
        $coreAchievementSet->save();

        // ===== new session =====
        $action = new ResumePlayerSessionAction();
        $action->execute($user, $game, $gameHash);

        $playerGame = PlayerGame::where('user_id', $user->id)->where('game_id', $game->id)->first();
        $this->assertNotNull($playerGame);
        $this->assertEquals($game->achievements_published, $playerGame->achievements_total); // TODO: remove if nothing using, can be queried from related game
        $this->assertEquals(0, $playerGame->achievements_unlocked);
        $this->assertEquals(0, $playerGame->achievements_unlocked_hardcore);
        $this->assertEquals(0, $playerGame->achievements_unlocked_softcore); // (unlocked - unlocked_hardcore); denormalized for distribution graph; remove after graph uses achievement_sets
        $this->assertEquals(0, $playerGame->completion_percentage);
        $this->assertEquals(0, $playerGame->completion_percentage_hardcore);
        $this->assertEquals($sessionStartAt, $playerGame->last_played_at);
        $this->assertEquals(0, $playerGame->playtime_total);
        $this->assertEquals(0, $playerGame->time_to_beat);
        $this->assertEquals(0, $playerGame->time_to_beat_hardcore);
        $this->assertEquals(null, $playerGame->beaten_dates);
        $this->assertEquals(null, $playerGame->beaten_dates_hardcore);
        $this->assertEquals(null, $playerGame->completion_dates);
        $this->assertEquals(null, $playerGame->completion_dates_hardcore);
        $this->assertEquals(null, $playerGame->beaten_at);
        $this->assertEquals(null, $playerGame->beaten_hardcore_at);
        $this->assertEquals(null, $playerGame->completed_at);
        $this->assertEquals(null, $playerGame->completed_hardcore_at);
        $this->assertEquals(null, $playerGame->last_unlock_at); // used as tiebreaker for game rankings; returned by API_GetUserCompletionProgress
        $this->assertEquals(null, $playerGame->last_unlock_hardcore_at); // used as tiebreaker for game rankings
        $this->assertEquals(null, $playerGame->first_unlock_at); // returned by API_GetUserCompletionProgress
        $this->assertEquals($game->points_total, $playerGame->points_total); // TODO: remove if nothing using, can be queried from related game
        $this->assertEquals(0, $playerGame->points);
        $this->assertEquals(0, $playerGame->points_hardcore);
        $this->assertEquals(0, $playerGame->points_weighted);

        $playerSession = PlayerSession::where('user_id', $user->id)->where('game_id', $game->id)->first();
        $this->assertNotNull($playerSession);
        $this->assertEquals($gameHash->id, $playerSession->game_hash_id);
        $this->assertEquals(0, $playerSession->hardcore);
        $this->assertEquals('Playing ' . $game->title, $playerSession->rich_presence);
        $this->assertEquals($sessionStartAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(1, $playerSession->duration); // initial session always 1 minute

        $playerAchievementSet = PlayerAchievementSet::where('user_id', $user->id)
            ->where('achievement_set_id', $coreAchievementSet->id)
            ->first();
        $this->assertNotNull($playerAchievementSet);
        $this->assertEquals(0, $playerAchievementSet->achievements_unlocked);
        $this->assertEquals(0, $playerAchievementSet->achievements_unlocked_hardcore);
        $this->assertEquals(0, $playerAchievementSet->achievements_unlocked_softcore); // (unlocked - unlocked_hardcore); denormalized for distribution graph
        $this->assertEquals(0, $playerAchievementSet->completion_percentage);
        $this->assertEquals(0, $playerAchievementSet->completion_percentage_hardcore);
        $this->assertEquals(0, $playerAchievementSet->time_taken);
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore);
        $this->assertEquals(null, $playerAchievementSet->completed_at);
        $this->assertEquals(null, $playerAchievementSet->completed_hardcore_at);
        $this->assertEquals(null, $playerAchievementSet->completion_dates);
        $this->assertEquals(null, $playerAchievementSet->completion_dates_hardcore);
        $this->assertEquals(null, $playerAchievementSet->last_unlock_at); // used as tiebreaker for game rankings; returned by API_GetUserCompletionProgress
        $this->assertEquals(null, $playerAchievementSet->last_unlock_hardcore_at); // used as tiebreaker for game rankings
        $this->assertEquals(0, $playerAchievementSet->points);
        $this->assertEquals(0, $playerAchievementSet->points_hardcore);
        $this->assertEquals(0, $playerAchievementSet->points_weighted);

        // ===== first ping at 30 seconds (less than a minute has elapsed, no playtime will be captured) =====
        $firstPingAt = $sessionStartAt->clone()->addSeconds(30);
        Carbon::setTestNow($firstPingAt);
        $action = new ResumePlayerSessionAction();
        $action->execute($user, $game, $gameHash, 'Titles');

        $playerGame->refresh();
        $this->assertEquals($firstPingAt, $playerGame->last_played_at);
        $this->assertEquals(0, $playerGame->playtime_total);

        $playerSession->refresh();
        $this->assertEquals('Titles', $playerSession->rich_presence);
        $this->assertEquals($firstPingAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(1, $playerSession->duration);

        $playerAchievementSet->refresh();
        $this->assertEquals(0, $playerAchievementSet->time_taken);
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore);

        // ===== second ping two minutes later, playtime will start to accumulate =====
        $secondPingAt = $firstPingAt->clone()->addMinutes(2);
        Carbon::setTestNow($secondPingAt);
        $action = new ResumePlayerSessionAction();
        $action->execute($user, $game, $gameHash, 'Level 1');

        $playerGame->refresh();
        $this->assertEquals($secondPingAt, $playerGame->last_played_at);
        $this->assertEquals(120, $playerGame->playtime_total); // session duration, in seconds

        $playerSession->refresh();
        $this->assertEquals('Level 1', $playerSession->rich_presence);
        $this->assertEquals($secondPingAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(2, $playerSession->duration); // session duration, in minutes

        $playerAchievementSet->refresh();
        $this->assertEquals(150, $playerAchievementSet->time_taken);
        $this->assertEquals(150, $playerAchievementSet->time_taken_hardcore); // hardcore assumed

        // ===== softcore unlock 45 seconds later (implicitly resumes session) =====
        $achievement = $game->achievements->first();
        $firstUnlockAt = $secondPingAt->clone()->addSeconds(45);
        Carbon::setTestNow($firstUnlockAt);
        (new UnlockPlayerAchievementAction())->execute($user, $achievement, false, $firstUnlockAt, null, $gameHash);

        $playerGame->refresh();
        $this->assertEquals($firstUnlockAt, $playerGame->last_played_at);
        $this->assertEquals(195, $playerGame->playtime_total); // unlock time - start time

        $playerSession->refresh();
        $this->assertEquals('Level 1', $playerSession->rich_presence);
        $this->assertEquals($firstUnlockAt, $playerSession->rich_presence_updated_at); // used to keep track of when session was last active, regardless of whether or not rich presence was updated
        $this->assertEquals(3, $playerSession->duration); // floor(195/60)

        $playerAchievementSet->refresh();
        $this->assertEquals(195, $playerAchievementSet->time_taken);
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore); // eliminate hardcore assumption

        // ===== third ping two minutes after second =====
        $thirdPingAt = $secondPingAt->clone()->addMinutes(2);
        Carbon::setTestNow($thirdPingAt);
        $action = new ResumePlayerSessionAction();
        $action->execute($user, $game, $gameHash, 'Level 2');

        $playerGame->refresh();
        $this->assertEquals($thirdPingAt, $playerGame->last_played_at);
        $this->assertEquals(255, $playerGame->playtime_total); // ping adjustments always rounded to nearest minute. will be fixed on next unlock

        $playerSession->refresh();
        $this->assertEquals('Level 2', $playerSession->rich_presence);
        $this->assertEquals($thirdPingAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(4, $playerSession->duration); // session duration, in minutes

        $playerAchievementSet->refresh();
        $this->assertEquals(255, $playerAchievementSet->time_taken); // ping adjustments always rounded to nearest minute. will be fixed on next unlock
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore); // hardcore assumed

        // ===== softcore unlock 72 seconds later =====
        $achievement = $game->achievements->skip(1)->first();
        $secondUnlockAt = $thirdPingAt->clone()->addSeconds(72);
        Carbon::setTestNow($secondUnlockAt);
        (new UnlockPlayerAchievementAction())->execute($user, $achievement, false, $secondUnlockAt, null, $gameHash);

        $playerGame->refresh();
        $this->assertEquals($secondUnlockAt, $playerGame->last_played_at);
        $this->assertEquals(342, $playerGame->playtime_total); // unlock time - start time

        $playerSession->refresh();
        $this->assertEquals('Level 2', $playerSession->rich_presence);
        $this->assertEquals($secondUnlockAt, $playerSession->rich_presence_updated_at); // used to keep track of when session was last active, regardless of whether or not rich presence was updated
        $this->assertEquals(5, $playerSession->duration); // floor(195/60)

        $playerAchievementSet->refresh();
        $this->assertEquals(342, $playerAchievementSet->time_taken);
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore); // eliminate hardcore assumption

        // ===== fourth ping two minutes after third =====
        $fourthPingAt = $thirdPingAt->clone()->addMinutes(2);
        Carbon::setTestNow($fourthPingAt);
        $action = new ResumePlayerSessionAction();
        $action->execute($user, $game, $gameHash, 'Level 2');

        $playerGame->refresh();
        $this->assertEquals($fourthPingAt, $playerGame->last_played_at);
        $this->assertEquals(402, $playerGame->playtime_total); // ping adjustments always rounded to nearest minute. will be fixed on next unlock

        $playerSession->refresh();
        $this->assertEquals('Level 2', $playerSession->rich_presence);
        $this->assertEquals($fourthPingAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(6, $playerSession->duration); // session duration, in minutes

        $playerAchievementSet->refresh();
        $this->assertEquals(402, $playerAchievementSet->time_taken); // ping adjustments always rounded to nearest minute. will be fixed on next unlock
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore); // hardcore assumed

        // ===== softcore unlock 41 seconds later =====
        $achievement = $game->achievements->skip(2)->first();
        $thirdUnlockAt = $fourthPingAt->clone()->addSeconds(41);
        Carbon::setTestNow($thirdUnlockAt);
        (new UnlockPlayerAchievementAction())->execute($user, $achievement, false, $thirdUnlockAt, null, $gameHash);

        $playerGame->refresh();
        $this->assertEquals($thirdUnlockAt, $playerGame->last_played_at);
        $this->assertEquals(431, $playerGame->playtime_total);

        $playerSession->refresh();
        $this->assertEquals('Level 2', $playerSession->rich_presence);
        $this->assertEquals($thirdUnlockAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(7, $playerSession->duration); // session duration, in minutes

        $playerAchievementSet->refresh();
        $this->assertEquals(431, $playerAchievementSet->time_taken); // ping adjustments always rounded to nearest minute. will be fixed on next unlock
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore); // hardcore assumed

        // ===== achievement set stops tracking time when beaten =====
        $fifthPingAt = $fourthPingAt->clone()->addMinutes(2);
        Carbon::setTestNow($fifthPingAt);
        $action = new ResumePlayerSessionAction();
        $action->execute($user, $game, $gameHash, 'Victory');

        $playerGame->refresh();
        $this->assertEquals($fifthPingAt, $playerGame->last_played_at);
        $this->assertEquals(491, $playerGame->playtime_total); // ping adjustments always rounded to nearest minute. will be fixed on next unlock

        $playerSession->refresh();
        $this->assertEquals('Victory', $playerSession->rich_presence);
        $this->assertEquals($fifthPingAt, $playerSession->rich_presence_updated_at);
        $this->assertEquals(8, $playerSession->duration); // session duration, in minutes

        $playerAchievementSet->refresh();
        $this->assertEquals(431, $playerAchievementSet->time_taken); // set tracking stops when completed
        $this->assertEquals(0, $playerAchievementSet->time_taken_hardcore); // hardcore assumed
    }
}
