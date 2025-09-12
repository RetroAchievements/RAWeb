<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Enums\Permissions;
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
        $result = $this->authenticateFromRequest($request);
        if ($result !== null) {
            return $this->buildResponse($result);
        }

        // without this, audit log entries created by the request
        // are not associated to a user.
        CauserResolver::setCauser($this->user);

        return parent::handleRequest($request);
    }

    private function authenticateFromRequest(Request $request): ?array
    {
        $username = request()->input('u');
        if (!$username) {
            // no user specified
            return [
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ];
        }

        // this pulls the user associated to the 't' parameter
        $this->user = request()->user('connect-token');

        if (!$this->user) {
            // no user found for provided token
            return [
                'Success' => false,
                'Status' => 401,
                'Code' => 'invalid_credentials',
                'Error' => 'Invalid user/token combination.',
            ];
        }

        if (strcasecmp($this->user->User, $username) === 0) {
            // matched user name
        } elseif (strcasecmp($this->user->display_name, $username) === 0) {
            // matched display name
        } else {
            // user associated to token doesn't match the username parameter provided
            return null;
        }

        $permissions = (int) $this->user->getAttribute('Permissions');
        if ($permissions < Permissions::Registered) {
            return $this->accessDenied(
                ($permissions === Permissions::Unregistered) ?
                    'Access denied. Please verify your email address.' :
                    'Access denied.'
                );
        }

        return null;
    }
}
