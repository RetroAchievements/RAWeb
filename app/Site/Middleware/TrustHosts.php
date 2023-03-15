<?php

declare(strict_types=1);

namespace App\Site\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     */
    public function hosts(): array
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
            '^(.+\.)?retroachievements\.org$',
        ];
    }
}
