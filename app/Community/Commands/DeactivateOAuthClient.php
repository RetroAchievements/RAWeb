<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Actions\DeactivateOAuthClientAction;
use App\Models\OAuthClient;
use Illuminate\Console\Command;

class DeactivateOAuthClient extends Command
{
    protected $signature = 'ra:community:oauth:deactivate-client {client : The OAuth client UUID}';
    protected $description = 'Revoke an OAuth client and every grant and token issued to it';

    public function handle(DeactivateOAuthClientAction $deactivateOAuthClient): int
    {
        $client = OAuthClient::query()->find($this->argument('client'));

        if (!$client) {
            $this->error('OAuth client not found.');

            return self::FAILURE;
        }

        $deactivateOAuthClient->execute($client);
        $this->info("OAuth client {$client->id} has been deactivated.");

        return self::SUCCESS;
    }
}
