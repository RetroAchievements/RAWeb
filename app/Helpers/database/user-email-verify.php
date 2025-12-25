<?php

use App\Enums\Permissions;
use App\Models\EmailConfirmation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function generateEmailVerificationToken(User $user): string
{
    $emailCookie = Str::random(16);

    EmailConfirmation::create([
        'user_id' => $user->id,
        'email_cookie' => $emailCookie,
        'expires_at' => Carbon::now()->addWeek(),
    ]);

    // Clear permissions til they validate their email.
    if (!$user->isBanned) {
        SetAccountPermissionsJSON('Server', Permissions::Moderator, $user->username, Permissions::Unregistered);
    }

    return $emailCookie;
}

/**
 * @deprecated will be replaced by Fortify and default framework features
 */
function validateEmailVerificationToken(string $emailCookie, ?User &$user): bool
{
    $emailConfirmation = EmailConfirmation::firstWhere('email_cookie', $emailCookie);

    if (!$emailConfirmation) {
        return false;
    }

    $user = User::find($emailConfirmation->user_id);
    if (!$user) {
        return false;
    }

    if ((int) $user->getAttribute('Permissions') !== Permissions::Unregistered) {
        return false;
    }

    $emailConfirmation->delete();

    $response = SetAccountPermissionsJSON('Server', Permissions::Moderator, $user->username, Permissions::Registered);
    if ($response['Success']) {
        static_addnewregistereduser($user->username);

        $user->email_verified_at = Carbon::now();
        $user->saveQuietly();

        generateAPIKey($user->username);

        // SUCCESS: validated email address for $user
        return true;
    }

    return false;
}

function deleteExpiredEmailVerificationTokens(): bool
{
    EmailConfirmation::where('expires_at', '<=', Carbon::now())->delete();

    return true;
}
