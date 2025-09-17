<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class HeaderTokenGuard extends TokenGuard
{
    protected string $headerName;

    public function __construct(
        UserProvider $provider,
        Request $request,
        string $inputKey = 'api_key',
        string $storageKey = 'api_token',
        bool $hash = false,
        string $headerName = 'X-API-Key',
    ) {
        parent::__construct($provider, $request, $inputKey, $storageKey, $hash);

        $this->headerName = $headerName;
    }

    public function getTokenForRequest(): ?string
    {
        // First, check the header.
        $token = $this->request->header($this->headerName);
        if (!empty($token)) {
            return $token;
        }

        $token = $this->request->bearerToken();
        if (!empty($token)) {
            return $token;
        }

        return parent::getTokenForRequest();
    }

    /**
     * Enforce case-insensitive key comparison.
     * We need to do this because the table uses utf8mb4_uca1400_ai_ci collation.
     * This mostly re-implements the underlying `TokenGuard::user()` method exactly.
     */
    public function user(): ?Authenticatable
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;
        $token = $this->getTokenForRequest();

        if (!empty($token)) {
            $user = $this->provider->retrieveByCredentials([
                $this->storageKey => $this->hash ? hash('sha256', $token) : $token,
            ]);

            if ($user && $user->{$this->storageKey} !== $token) {
                $user = null;
            }
        }

        return $this->user = $user;
    }
}
