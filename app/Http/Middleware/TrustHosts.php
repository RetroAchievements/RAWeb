<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts(): array
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
            '^(.+\.)?retroachievements\.org$',
            '^10\.0\.0\.\d+(:\d+)?$', // private network hosts for server-to-server requests
        ];
    }
}
