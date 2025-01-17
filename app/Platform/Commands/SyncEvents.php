<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\EventAward;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEvents extends Command
{
    protected $signature = 'ra:sync:events';
    protected $description = 'Sync events from event games';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameConversions = [
            1963 => new ConvertAsIs('solar-jetman'),
            11128 => new ConvertAsIs('pumpkin-king-2017', '10/13/2017', '10/31/2017'),
            11597 => new ConvertAsIs('christmas-2017', '12/1/2017', '1/7/2018'),
            13279 => new ConvertToTracked('aotw-2018-halloween', [
                16187 => ['10/6/2018', '10/12/2018'],
                15585 => ['10/13/2018', '10/19/2018'],
                15025 => ['10/20/2018', '10/26/2018'],
                3230 => ['10/27/2018', '11/2/2018'],
            ]),
            13447 => new ConvertToTracked('aotw-2018-christmas', [
                601 => ['11/30/2018', '12/6/2018'],
                2530 => ['12/7/2018', '12/13/2018'],
                31510 => ['12/14/2018', '12/20/2018'],
                31278 => ['12/21/2018', '12/27/2018'],
            ]),
            13448 => new ConvertToTiered('devember-2018', [1 => '1 set fixed', 2 => '2 sets fixed'], [
                68551 => ['Hotscrock'],
                68552 => ['BenGhazi'],
                68553 => ['Keltron3030', 'televandalist'],
                68554 => ['SporyTike', 'kdecks', 'Salsa', 'JAM'],
                68555 => ['Thoreau', 'Blazekickn', 'theztret00', 'Grenade44', 'DrPixel', 'ColonD', 'Tutumos'],
                68556 => ['Zaphnath', 'Rimsala', 'SirVG', 'Jamiras', 'devidokop', 'MGNS8M', 'ikki5'],
            ])
        ];

        $gameCount = count($gameConversions);

        $this->info("\nUpserting {$gameCount} events derived from event games.");
        $progressBar = $this->output->createProgressBar($gameCount);

        foreach ($gameConversions as $gameId => $conversion) {
            $conversion->convert($this, $gameId);
            $progressBar->advance();
        }

        $progressBar->finish();

        $this->info("\nDone.");
    }
}

class ConvertGame
{
    protected string $slug;
    protected ?string $activeFrom = null;
    protected ?string $activeThrough = null;

    public function convert(Command $command, int $gameId): void
    {
        $game = Game::find($gameId);
        if (!$game) {
            $command->error("Game $gameID not found");
            return;
        }

        $event = $game->event;
        if (!$event) {
            $event = Event::create([
                'legacy_game_id' => $game->ID,
                'slug' => $this->slug,
                'image_asset_path' => $game->ImageIcon,
            ]);
        }

        if ($this->activeFrom && $this->activeThrough) {
            $event->active_from = Carbon::parse($this->activeFrom);
            $event->active_through = Carbon::parse($this->activeThrough);
            $event->save();
        }

        $this->convertSiteAwards($event);

        $this->process($command, $event);
    }

    protected function process(Command $command, Event $event): void
    {
    }

    protected function convertSiteAwards(Event $event): void
    {
        // only convert hardcore badges
        $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 1);

        foreach ($badges->get() as $badge) {
            $badge->AwardType = AwardType::Event;
            $badge->AwardData = $event->id;
            $badge->AwardDataExtra = 0;
            $badge->save();
        }
        
        // delete softcore badges
        PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 0)
            ->delete();
    }

    protected function setAchievementCount(Event $event, int $count): void
    {
        // find the specified number of published achievements, and demote the rest
        $publishedCount = 0;
        $unofficialCount = 0;
        for ($index = 0; $index < $event->legacyGame->achievements->count(); $index++)
        {
            $achievement = $event->legacyGame->achievements->skip($index)->first();
            if ($achievement->Flags === AchievementFlag::OfficialCore->value)
            {
                if ($publishedCount === $count)
                {
                    $achievement->Flags = AchievementFlag::Unofficial->value;
                    $achievement->save();
                }
                else
                {
                    $publishedCount++;
                }
            }
            else
            {
                $unofficialCount++;
            }
        }

        // didn't find enough published achievements, look for unpublished achievements to promote
        if ($publishedCount < $count && $unofficialCount > 0)
        {
            for ($index = 0; $index < $event->legacyGame->achievements->count(); $index++)
            {
                $achievement = $event->legacyGame->achievements->skip($index)->first();
                if ($achievement->Flags === AchievementFlag::Unofficial->value)
                {
                    $achievement->Flags = AchievementFlag::OfficialCore->value;
                    $achievement->save();

                    $publishedCount++;
                    if ($publishedCount === $count)
                    {
                        break;
                    }
                }
            }
        }

        $event->legacyGame->achievements_published = $publishedCount;
        $event->legacyGame->save();
    }

    protected function createEventAchievement(Command $command, Achievement $achievement, int $sourceAchievementId,
        ?Carbon $activeFrom = null, ?Carbon $activeThrough = null): EventAchievement
    {
        if (!Achievement::exists($sourceAchievementId)) {
            $command->error("Could not find source achievement: $sourceAchievementId");
            return null;
        }

        $eventAchievement = EventAchievement::where('achievement_id', $achievement->id)->first();
        if (!$eventAchievement) {
            $eventAchievement = EventAchievement::create([
                'achievement_id' => $achievement->id,
                'source_achievement_id' => $sourceAchievementId,
                'active_from' => $activeFrom,
                'active_through' => $activeThrough,
            ]);
        } else {
            $eventAchievement->source_achievement_id = $sourceAchievementId;
            $eventAchievement->active_from = $activeFrom;
            $eventAchievement->active_through = $activeThrough;
            $eventAchievement->save();
        }

        // update unlock timestamps on the event achievements to match the source unlock
        $winners = PlayerAchievement::where('achievement_id', $sourceAchievementId)
            ->where('unlocked_hardcore_at', '>=', $eventAchievement->active_from)
            ->where('unlocked_hardcore_at', '<', $eventAchievement->active_until);
        foreach ($winners->get() as $winner) {
            $unlock = PlayerAchievement::where('achievement_id', $achievement->id)
                ->where('user_id', $winner->user_id)
                ->first();
            if ($unlock) {
                $unlock->unlocked_hardcore_at = $unlock->unlocked_at = $winner->unlocked_hardcore_at;
                $unlock->save();
            }
        }

        // EventAchievementObserver will copy data and additional unlocks for anyone that
        // wasn't awarded the badge from the source achievement
        $eventAchievement->save();

        return $eventAchievement;
    }
}

class ConvertAsIs extends ConvertGame
{
    public function __construct(string $slug, ?string $activeFrom = null, ?string $activeThrough = null) {
        $this->slug = $slug;
        $this->activeFrom = $activeFrom;
        $this->activeThrough = $activeThrough;
    }
}

class ConvertCollapse extends ConvertGame
{
    public function __construct(string $slug) {
        $this->slug = $slug;
    }

    protected function process(Command $command, Event $event): void
    {
        $first = true;
        foreach ($event->legacyGame->achievements as $achievement)
        {
            if ($first)
            {
                $first = false;

                $eventAchievement = EventAchievement::where('achievement_id', $achievement->id)->first();
                if (!$eventAchievement) {
                    $eventAchievement = EventAchievement::create(['achievement_id' => $achievement->id]);
                }

                $achievement->Flags = AchievementFlag::OfficialCore->value;
                $achievement->Title = $event->Title;
                if (empty(trim($achievement->Description))) {
                    $achievement->Description = "Earned enough points for the badge";
                }
            }
            else
            {
                $achievement->Flags = AchievementFlag::Unofficial->value;
            }

            $achievement->save();
        }
    }
}

class ConvertToTracked extends ConvertGame
{
    protected array $achievements;

    public function __construct(string $slug, array $achievements) {
        $this->slug = $slug;
        $this->achievements = $achievements;
    }

    protected function process(Command $command, Event $event): void
    {
        $this->setAchievementCount($event, count($this->achievements));

        // convert achievements to event achievements
        $index = 0;
        foreach ($this->achievements as $sourceAchievementId => $dates)
        {
            $achievement = $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->skip($index)->first();
            if (!$achievement) {
                $command->error("Could not find achievement[$index]");
                return;
            }

            $eventAchievement = $this->createEventAchievement($command, $achievement, $sourceAchievementId, Carbon::parse($dates[0]), Carbon::parse($dates[1]));

            if ($index === 0) {
                $event->active_from = $eventAchievement->active_from;
            }
    
            $index++;
        }

        $event->active_until = $eventAchievement->active_until;
        $event->save();

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($event->legacyGame->id))->onQueue('game-metrics');
    }
}

class ConvertToTiered extends ConvertGame
{
    protected array $tiers;
    protected array $achievements;

    public function __construct(string $slug, array $tiers, array $achievements) {
        $this->slug = $slug;
        $this->tiers = $tiers;
        $this->achievements = $achievements;
    }

    protected function convertSiteAwards(Event $event): void
    {
        // do not process site awards here, we'll do it later so we can assign tiers
    }

    protected function process(Command $command, Event $event): void
    {
        $this->setAchievementCount($event, count($this->achievements));

        $tier_counts = [];
        $tier_index = 1;
        foreach ($this->tiers as $count => $label)
        {
            $eventAward = EventAward::where('event_id', $event->id)
                ->where('tier_index', $tier_index)
                ->first();
            
            if (!$eventAward)
            {
                $eventAward = EventAward::create([
                    'event_id' => $event->id,
                    'tier_index' => $tier_index,
                    'label' => $label,
                    'achievements_required' => $count,
                    'image_asset_path' => $event->image_asset_path,
                ]);
            }

            $tier_counts[] = $count;
            $tier_index++;
        }

        // convert achievements to event achievements
        $tier_index = count($tier_counts);
        $count = count($this->achievements);
        $index = 0;
        $allUserIds = [];
        foreach ($this->achievements as $achievementId => $users)
        {
            $achievement = Achievement::find($achievementId);
            if (!$achievement) {
                $command->error("Could not find achievement: $achievementId");
                return;
            }

            $userIds = User::whereIn('User', $users)->withTrashed()->pluck('ID')->toArray();
            foreach ($userIds as $userId) {
                // find hardcore badge
                $badge = PlayerBadge::where('AwardType', AwardType::Mastery)
                    ->where('AwardData', $event->legacyGame->id)
                    ->where('AwardDataExtra', 1)
                    ->where('user_id', $userId)
                    ->first();
                if (!$badge) {
                    // hardcore badge not found, look for softcore badge
                    $badge = PlayerBadge::where('AwardType', AwardType::Mastery)
                        ->where('AwardData', $event->legacyGame->id)
                        ->where('AwardDataExtra', 0)
                        ->where('user_id', $userId)
                        ->first();
                }

                if ($badge) {
                    // convert found badge
                    $badge->AwardType = AwardType::Event;
                    $badge->AwardData = $event->id;
                    $badge->AwardDataExtra = $tier_index;
                    $badge->save();
                }
            }

            $allUserIds = array_merge($allUserIds, $userIds);

            // delete all unlocks for users not at this tier
            PlayerAchievement::where('achievement_id', $achievementId)
                ->whereNotIn('user_id', $allUserIds)
                ->delete();

            // update any softcore unlocks at this tier to hardcore
            PlayerAchievement::where('achievement_id', $achievementId)
                ->whereNull('unlocked_hardcore_at')
                ->update(['unlocked_hardcore_at' => DB::raw('unlocked_at')]);

            // update tier_index if crossing a threshold
            if ($tier_index > 0 && $tier_counts[$tier_index - 1] === $count) {
                $tier_index--;
            }
            $count--;

            $index++;
        }

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($event->legacyGame->id))->onQueue('game-metrics');

        // delete any remaining badges
        PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 0)
            ->delete();

        foreach ($allUserIds as $userId)
        {
            dispatch(new UpdatePlayerGameMetricsJob($userId, $event->legacyGame->id));
        }
    }
}

