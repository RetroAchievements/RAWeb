<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * @deprecated - remove after this executes once on prod
 */
class AddStandaloneSystem extends Command
{
    protected $signature = 'ra:platform:system:add-standalones';
    protected $description = 'Set up the Standalones system in the database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Adding the Standalones system to the DB.');

        $standalonesConfig = $this->loadStandalonesConfig();

        if (!$standalonesConfig) {
            $this->error('Something went wrong loading the Standalones system config.');

            return;
        }

        $this->insertStandalonesSystemToDb($standalonesConfig);

        $this->info('Added the Standalones system (' . $standalonesConfig['id'] . ') to the DB.');
    }

    private function loadStandalonesConfig(): ?array
    {
        $standalonesConfig = null;

        $systemsConfig = config("systems");
        foreach ($systemsConfig as $id => $config) {
            if (isset($config['name']) && $config['name'] === 'Standalones') {
                $standalonesConfig = $config;
                $standalonesConfig['id'] = $id;

                break;
            }
        }

        return $standalonesConfig;
    }

    private function insertStandalonesSystemToDb(array $standalonesConfig): void
    {
        $doesStandalonesSystemExist = DB::table('Console')->where('ID', $standalonesConfig['id'])->exists();
        if (!$doesStandalonesSystemExist) {
            DB::table('Console')->insert([
                'ID' => $standalonesConfig['id'],
                'Name' => $standalonesConfig['name'],
            ]);
        }
    }
}
