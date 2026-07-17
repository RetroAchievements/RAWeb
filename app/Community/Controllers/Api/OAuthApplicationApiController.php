<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\CreateOAuthClientAction;
use App\Community\Actions\DeactivateOAuthClientAction;
use App\Community\Requests\StoreOAuthClientRequest;
use App\Community\Requests\UpdateOAuthClientRequest;
use App\Data\OAuthClientCredentialsData;
use App\Data\OAuthClientData;
use App\Http\Controller;
use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\ClientRepository;

class OAuthApplicationApiController extends Controller
{
    public function store(StoreOAuthClientRequest $request, CreateOAuthClientAction $createOAuthClient): JsonResponse
    {
        $this->authorize('create', OAuthClient::class);

        /** @var User $user */
        $user = $request->user();

        $data = $request->validated();

        $client = $createOAuthClient->execute(
            user: $user,
            name: $data['name'],
            redirectUris: $data['redirectUris'],
            isConfidential: $data['type'] === 'confidential',
            enableDeviceFlow: (bool) ($data['enableDeviceFlow'] ?? false),
        );

        return response()->json(new OAuthClientCredentialsData((string) $client->id, $client->plainSecret));
    }

    public function update(UpdateOAuthClientRequest $request, OAuthClient $client): JsonResponse
    {
        $this->authorize('update', $client);

        $data = $request->validated();

        $client->forceFill([
            'name' => $data['name'],
            'redirect_uris' => $data['redirectUris'],
        ])->save();

        return response()->json(OAuthClientData::fromClient($client->refresh()));
    }

    public function destroy(OAuthClient $client, DeactivateOAuthClientAction $deactivateOAuthClient): JsonResponse
    {
        $this->authorize('delete', $client);

        $deactivateOAuthClient->execute($client);

        return response()->json(['success' => true]);
    }

    public function regenerateSecret(OAuthClient $client, ClientRepository $clients): JsonResponse
    {
        $this->authorize('update', $client);

        abort_unless($client->confidential(), 422);

        $clients->regenerateSecret($client);

        return response()->json(new OAuthClientCredentialsData((string) $client->id, $client->plainSecret));
    }
}
