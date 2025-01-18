<?php

declare(strict_types=1);

namespace App\Support\Rules;

use App\Models\User;
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

        // For new registrations, do simple uniqueness checks.
        if (!$user) {
            return array_merge($baseRules, [
                "unique:{$table},User",
                "unique:{$table},display_name",
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
