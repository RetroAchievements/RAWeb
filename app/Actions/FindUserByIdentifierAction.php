<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class FindUserByIdentifierAction
{
    public function execute(?string $identifier): ?User
    {
        if ($identifier === null) {
            return null;
        }

        $ulidLength = 26;
        if (mb_strlen($identifier) === $ulidLength) {
            return User::whereUlid($identifier)->first();
        }

        return User::query()
            ->where(function ($query) use ($identifier) {
                $query->where('display_name', $identifier)
                    ->orWhere('User', $identifier);
            })
            ->first();
    }
}
