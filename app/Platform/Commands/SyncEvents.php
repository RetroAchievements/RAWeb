<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;
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
        // demote extra achievements first so player metrics calculation will correctly
        // identify completion
        $index = $event->legacyGame->achievements->count();
        while ($index > count($this->achievements))
        {
            $index--;
            $achievement = $event->legacyGame->achievements->skip($index)->first();
            $achievement->Flags = AchievementFlag::Unofficial->value;
            $achievement->save();
        }
        $event->legacyGame->achievements_published = $index;
        $event->legacyGame->save();

        // convert achievements to event achievements
        $index = 0;
        foreach ($this->achievements as $sourceAchievementId => $dates)
        {
            $achievement = $event->legacyGame->achievements->skip($index)->first();
            if (!$achievement) {
                $command->error("Could not find achievement[$index]");
                return;
            }

            if (!Achievement::exists($sourceAchievementId)) {
                $command->error("Could not find source achievement: $sourceAchievementId");
                return;
            }

            $eventAchievement = EventAchievement::where('achievement_id', $achievement->id)->first();
            if (!$eventAchievement) {
                $eventAchievement = EventAchievement::create([
                    'achievement_id' => $achievement->id,
                    'source_achievement_id' => $sourceAchievementId,
                    'active_from' => Carbon::parse($dates[0]),
                    'active_through' => Carbon::parse($dates[1]),
                ]);
            } else {
                $eventAchievement->source_achievement_id = $sourceAchievementId;
                $eventAchievement->active_from = Carbon::parse($dates[0]);
                $eventAchievement->active_through = Carbon::parse($dates[1]);
                $eventAchievement->save();
            }

            if ($index === 0) {
                $event->active_from = $eventAchievement->active_from;
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

            $index++;
        }

        $event->active_until = $eventAchievement->active_until;
        $event->save();

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($event->legacyGame->id))->onQueue('game-metrics');
    }
}
