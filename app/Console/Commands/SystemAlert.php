<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Settings\Settings;
use Illuminate\Console\Command;

class SystemAlert extends Command
{
    protected $signature = 'ra:settings:system-alert {message?}';
    protected $description = 'Set system alert message';

    public function __construct(
        private Settings $settings
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $message = $this->argument('message');
        $message = is_array($message) ? $message[0] : $message;

        $this->settings->put('system.alert', $message);
    }
}
