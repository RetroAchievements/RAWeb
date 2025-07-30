<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\TokenGuard;
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
        string $headerName = 'X-API-Key'
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
}
