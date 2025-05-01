<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseAuthenticatedApiAction extends BaseApiAction
{
    protected ?User $user;

    public function handleRequest(Request $request): JsonResponse
    {
        if (!$this->authenticate($request)) {
            return $this->buildResponse($this->accessDenied());
        }

        return parent::handleRequest($request);
    }

    private function authenticate(Request $request): bool
    {
        $username = request()->input('u');
        if (!$username) {
            // no user specified
            return false;
        }

        // this pulls the user associated to the 't' parameter
        $this->user = request()->user('connect-token');

        if (!$this->user) {
            // no user found for provided token
            return false;
        }

        if (strcasecmp($this->user->User, $username) === 0) {
            // matched user name
            return true;
        }

        if (strcasecmp($this->user->display_name, $username) === 0) {
            // matched display name
            return true;
        }

        // user associated to token doesn't match the username parameter provided
        return false;
    }
}
