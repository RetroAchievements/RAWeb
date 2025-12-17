<?php

declare(strict_types=1);

namespace App\Api\V2\Users;

use App\Actions\FindUserByIdentifierAction;
use LaravelJsonApi\Eloquent\Contracts\Driver;
use LaravelJsonApi\Eloquent\Contracts\Parser;
use LaravelJsonApi\Eloquent\Repository;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Custom repository to support flexible identifier lookup (ULID, display_name, or username).
 * The base Repository only supports lookup by the schema's ID field.
 */
class UserRepository extends Repository
{
    private Parser $userParser;

    public function __construct(Schema $schema, Driver $driver, Parser $parser)
    {
        parent::__construct($schema, $driver, $parser);
        $this->userParser = $parser;
    }

    public function find(string $resourceId): ?object
    {
        $user = app(FindUserByIdentifierAction::class)->execute($resourceId);

        // Exclude banned and unverified users from the show endpoint.
        if ($user && !$user->isBanned && $user->email_verified_at !== null) {
            return $this->userParser->parseNullable($user);
        }

        return null;
    }

    public function exists(string $resourceId): bool
    {
        $user = app(FindUserByIdentifierAction::class)->execute($resourceId);

        return $user !== null && !$user->isBanned && $user->email_verified_at !== null;
    }
}
