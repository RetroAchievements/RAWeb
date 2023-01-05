<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;

class UpdatePlayerRanks extends Command
{
    protected $signature = 'ra:platform:player:update-ranks';
    protected $description = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
    }
}
