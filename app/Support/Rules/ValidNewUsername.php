<?php

declare(strict_types=1);

namespace App\Support\Rules;

use App\Models\User;
use App\Models\UserUsername;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ValidNewUsername
{
    public static function get(?User $user = null): array
    {
        $baseRules = [
            'required',
            'min:4',
            'max:20',
            new CtypeAlnum(),
        ];

        // We can't use the "mysql." prefix for SQLite (tests).
        $table = DB::connection()->getDriverName() === 'sqlite' ? 'UserAccounts' : 'mysql.UserAccounts';

        // For new registrations, block both existing usernames and pending requests.
        if (!$user) {
            return array_merge($baseRules, [
                "unique:{$table},User",
                "unique:{$table},display_name",
                function (string $attribute, mixed $value, Closure $fail) {
                    // Check if this username is pending approval for anyone.
                    $hasPendingRequest = UserUsername::pending()
                        ->where('username', $value)
                        ->exists();

                    if ($hasPendingRequest) {
                        $fail('This username is currently unavailable.');
                    }
                },
            ]);
        }

        // For username change requests, exclude the current user from
        // unique checks. This allows them to revert back to their
        // original username after they set a display_name.
        return array_merge($baseRules, [
            Rule::unique($table, 'User')->ignore($user->id, 'ID'),
            Rule::unique($table, 'display_name')->ignore($user->id, 'ID'),
        ]);
    }
}
