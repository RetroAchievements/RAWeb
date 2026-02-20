<?php

declare(strict_types=1);

namespace App\Support\Alerts\Jobs;

use App\Support\Alerts\Alert;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAlertWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30];

    /**
     * Optional HTTP client for testing. Not serialized with the job.
     */
    public ?Client $client = null;

    public function __construct(
        public readonly Alert $alert,
        public readonly string $webhookUrl,
    ) {
    }

    public function handle(): void
    {
        $client = $this->client ?? new Client();

        $payload = [
            'content' => $this->alert->toDiscordMessage(),
        ];

        try {
            $client->post($this->webhookUrl, [
                'json' => $payload,
                'timeout' => 10,
            ]);
        } catch (GuzzleException $e) {
            Log::error('Failed to send alert webhook', [
                'alert_type' => $this->alert::type(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'alert:' . $this->alert::type(),
        ];
    }
}
