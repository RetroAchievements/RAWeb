<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Community\Actions\RecordOAuthGrantAction;
use App\Models\User;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\Request;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Symfony\Component\HttpFoundation\Response;

class RecordApprovedOAuthGrant
{
    public function __construct(private RecordOAuthGrantAction $recordOAuthGrant)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $grant = $this->grantDetails($request);
        $response = $next($request);

        if ($response->isSuccessful() || $response->isRedirection()) {
            /** @var User|null $user */
            $user = $request->user();

            if ($user && $grant) {
                $this->recordOAuthGrant->execute($user, $grant['clientId'], $grant['scopes']);
            }
        }

        return $response;
    }

    /** @return array{clientId: string, scopes: string[]}|null */
    private function grantDetails(Request $request): ?array
    {
        $serialized = $request->session()->get('authRequest') ?? $request->session()->get('deviceCode');

        if (!is_string($serialized)) {
            return null;
        }

        $grant = unserialize($serialized, ['allowed_classes' => [
            \League\OAuth2\Server\RequestTypes\AuthorizationRequest::class,
            \Laravel\Passport\Bridge\Client::class,
            \Laravel\Passport\Bridge\Scope::class,
            \Laravel\Passport\Bridge\User::class,
            \Laravel\Passport\Bridge\DeviceCode::class,
            DateTimeImmutable::class,
        ]]);

        if (!$grant instanceof AuthorizationRequestInterface && !$grant instanceof DeviceCodeEntityInterface) {
            return null;
        }

        return [
            'clientId' => $grant->getClient()->getIdentifier(),
            'scopes' => array_map(fn ($scope): string => $scope->getIdentifier(), $grant->getScopes()),
        ];
    }
}
