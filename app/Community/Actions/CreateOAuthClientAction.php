<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\ClientRepository;

class CreateOAuthClientAction
{
    public function __construct(private readonly ClientRepository $clients)
    {
    }

    /**
     * The quota lives here rather than in the policy because being at the limit is
     * a transient state of the user's data, not a missing permission.
     *
     * @param string[] $redirectUris
     */
    public function execute(
        User $user,
        string $name,
        array $redirectUris,
        bool $isConfidential,
        bool $enableDeviceFlow = false,
    ): OAuthClient {
        $maxApplications = max(1, (int) config('oauth.max_applications_per_user'));

        if (OAuthClient::query()->active()->ownedBy($user)->count() >= $maxApplications) {
            throw ValidationException::withMessages([
                'name' => "You can only have {$maxApplications} active applications. Deactivate one to register another.",
            ]);
        }

        /** @var OAuthClient $client */
        $client = $this->clients->createAuthorizationCodeGrantClient(
            name: $name,
            redirectUris: $redirectUris,
            confidential: $isConfidential,
            user: $user,
            enableDeviceFlow: $enableDeviceFlow,
        );

        return $client;
    }
}
