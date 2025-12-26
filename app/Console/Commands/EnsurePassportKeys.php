<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsurePassportKeys extends Command
{
    protected $signature = 'passport:ensure-passport-keys';
    protected $description = 'Ensure that Laravel Passport encryption keys exist';

    public function handle(): int
    {
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        if (file_exists($privateKeyPath) && file_exists($publicKeyPath)) {
            $this->components->info('Passport keys already exist. To regenerate them, run "php artisan passport:keys --force".');

            return Command::SUCCESS;
        }

        $this->call('passport:keys');
        $this->components->info('Passport keys have been generated.');

        return Command::SUCCESS;
    }
}
