<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PasswordResetToken;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DeleteExpiredPasswordResetTokens extends Command
{
    protected $signature = 'ra:site:user:delete-expired-password-reset-tokens';
    protected $description = 'Delete expired password reset tokens';

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        PasswordResetToken::where('created_at', '<=', Carbon::now()->subDays(5))->delete();
    }
}
