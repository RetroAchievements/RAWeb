<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\CreateAchievementOfTheWeek as CreateAchievementOfTheWeekAction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CreateAchievementOfTheWeek extends Command
{
    protected $signature = "ra:platform:event:create-aotw
                            {startDate : Date of first AotW (i.e. 2023-01-02)}
                            {achievementIds? : CSV of achievements}";
    protected $description = "Creates an AotW event for a given year";

    public function handle(): void
    {
        $startDate = Carbon::parse($this->argument('startDate'));
        $achievementIds = array_filter(explode(',', $this->argument('achievementIds') ?? ''));

        $event = (new CreateAchievementOfTheWeekAction())->execute($startDate, $achievementIds);

        $count = count($achievementIds);
        if ($count === 0) {
            $this->info("Initialized event " . $event->id);
        } else {
            $this->info("Initialized event " . $event->id . " with $count achievements");
        }
    }
}
