<?php

use App\Enums\Permissions;
use App\Models\EmailConfirmation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function generateEmailVerificationToken(User $user): string
{
    $emailCookie = Str::random(16);
    $expiry = date('Y-m-d', time() + 60 * 60 * 24 * 7);

    EmailConfirmation::create([
        'User' => $user->username,
        'user_id' => $user->id,
        'EmailCookie' => $emailCookie,
        'Expires' => $expiry,
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
function validateEmailVerificationToken(string $emailCookie, ?string &$user): bool
{
    $emailConfirmation = EmailConfirmation::firstWhere('EmailCookie', $emailCookie);

    if (!$emailConfirmation) {
        return false;
    }

    $user = User::find($emailConfirmation->user_id);
    // TODO delete after dropping User from EmailConfirmations
    if (!$user) {
        $user = User::firstWhere('User', $emailConfirmation->User);
    }
    // ENDTODO delete after dropping User from EmailConfirmations

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
        $user->save();

        generateAPIKey($user->username);

        // SUCCESS: validated email address for $user
        return true;
    }

    return false;
}

function deleteExpiredEmailVerificationTokens(): bool
{
    EmailConfirmation::where('Expires', '<=', Carbon::today())->delete();

    return true;
}
