<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Actions\GenerateAnnualRecapAction;
use App\Community\Jobs\GenerateAnnualRecapJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateAnnualRecap extends Command
{
    protected $signature = 'ra:community:generate-annual-recap ' .
                           '{userId? : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}';

    protected $description = 'Generates an annual summary email for the provided user';

    public function __construct(
        private readonly GenerateAnnualRecapAction $generateAnnualRecapAction
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');
        if ($userId) {
            $user = is_numeric($userId)
                ? User::findOrFail($userId)
                : User::where('User', $userId)->firstOrFail();

            $this->generateAnnualRecapAction->execute($user);
        } else {
            $year = Carbon::now()->subMonths(6)->year;
            $december = Carbon::create($year, 12, 1, 0, 0, 0);
            $september = Carbon::create($year, 9, 1, 0, 0, 0);

            $users = User::where('LastLogin', '>=', $december)
                ->where('Created', '<', $september)
                ->orderByDesc('LastLogin')
                ->pluck('ID');

            foreach ($users as $userId) {
                GenerateAnnualRecapJob::dispatch($userId)->onQueue('player-metrics');
            }
        }
    }
}
