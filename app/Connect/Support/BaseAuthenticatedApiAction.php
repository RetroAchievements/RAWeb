<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\CauserResolver;

abstract class BaseAuthenticatedApiAction extends BaseApiAction
{
    protected ?User $user;

    protected function authenticate(): bool
    {
        // NOTE: CauserResolver not needed here. audit log entries
        // are correctly associated to the logged in user.
        $this->user = auth()->user();

        return $this->user !== null;
    }

    public function handleRequest(Request $request): JsonResponse
    {
        if (!$this->authenticateFromRequest($request)) {
            return $this->buildResponse($this->accessDenied());
        }

        // without this, audit log entries created by the request
        // are not associated to a user.
        CauserResolver::setCauser($this->user);

        return parent::handleRequest($request);
    }

    private function authenticateFromRequest(Request $request): bool
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
