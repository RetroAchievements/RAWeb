<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\StoreUsernameChangeData;
use App\Community\Data\UpdateEmailData;
use App\Community\Data\UpdateLocaleData;
use App\Community\Data\UpdatePasswordData;
use App\Community\Data\UpdateProfileData;
use App\Community\Data\UpdateWebsitePrefsData;
use App\Community\Data\UserSettingsPagePropsData;
use App\Community\Enums\ArticleType;
use App\Community\Requests\ResetConnectApiKeyRequest;
use App\Community\Requests\ResetWebApiKeyRequest;
use App\Community\Requests\StoreUsernameChangeRequest;
use App\Community\Requests\UpdateEmailRequest;
use App\Community\Requests\UpdateLocaleRequest;
use App\Community\Requests\UpdatePasswordRequest;
use App\Community\Requests\UpdateProfileRequest;
use App\Community\Requests\UpdateWebsitePrefsRequest;
use App\Data\RoleData;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Http\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserUsername;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserSettingsController extends Controller
{
    public function show(): InertiaResponse
    {
        $this->authorize('updateSettings');

        /** @var User $user */
        $user = Auth::user();

        $user->load(['roles' => function ($query) {
            $query->where('display', '>', 0);
        }]);

        $userSettings = UserData::fromUser($user)->include(
            'apiKey',
            'deleteRequested',
            'emailAddress',
            'motto',
            'userWallActive',
            'visibleRole',
        );

        $can = UserPermissionsData::fromUser($user)->include(
            'createUsernameChangeRequest',
            'manipulateApiKeys',
            'updateAvatar',
            'updateMotto'
        );

        $requestedUsername = UserUsername::whereUserId($user->id)
            ->pending()
            ->latest('created_at')
            ->first()
            ?->username;

        /** @var Collection<int, Role> $displayableRoles */
        $displayableRoles = $user->roles;

        $mappedRoles = $displayableRoles->map(fn ($role) => RoleData::fromRole($role))
            ->values()
            ->all();

        $props = new UserSettingsPagePropsData(
            $userSettings,
            $can,
            $mappedRoles,
            $requestedUsername
        );

        return Inertia::render('settings', $props);
    }

    public function storeUsernameChangeRequest(StoreUsernameChangeRequest $request): JsonResponse
    {
        $this->authorize('create', UserUsername::class);

        $data = StoreUsernameChangeData::fromRequest($request);

        /** @var User $user */
        $user = $request->user();

        $isOnlyCapitalizationChange = strtolower($user->display_name) === strtolower($data->newDisplayName);
        if ($isOnlyCapitalizationChange) {
            $user->display_name = $data->newDisplayName;
            $user->save();
        } else {
            UserUsername::create([
                'user_id' => $user->id,
                'username' => $data->newDisplayName,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $data = UpdatePasswordData::fromRequest($request);

        /** @var User $user */
        $user = $request->user();

        changePassword($user->username, $data->newPassword);
        generateAppToken($user->username, $tokenInOut);

        return response()->json(['success' => true]);
    }

    public function updateEmail(UpdateEmailRequest $request): JsonResponse
    {
        $data = UpdateEmailData::fromRequest($request);

        /** @var User $user */
        $user = $request->user();

        // The user will need to reconfirm their email address.
        $user->EmailAddress = $data->newEmail;
        $user->setAttribute('Permissions', Permissions::Unregistered);
        $user->roles()->detach();
        $user->email_verified_at = null;
        $user->save();

        // TODO move this to an action, use Fortify, do something else.
        // sendValidationEmail cannot be invoked while under test.
        if (app()->environment() !== 'testing') {
            sendValidationEmail($user, $data->newEmail);
        }

        addArticleComment(
            'Server',
            ArticleType::UserModeration,
            $user->id,
            "{$user->display_name} changed their email address"
        );

        return response()->json(['success' => true]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = UpdateProfileData::fromRequest($request);

        /** @var User $user */
        $user = $request->user();

        $user->update($data->toArray());

        return response()->json(['success' => true]);
    }

    public function updateLocale(UpdateLocaleRequest $request): JsonResponse
    {
        $data = UpdateLocaleData::fromRequest($request);

        /** @var User $user */
        $user = $request->user();

        $user->locale = $data->locale;
        $user->save();

        return response()->json(['success' => true]);
    }

    // TODO migrate to $user->preferences blob
    public function enableSuppressMatureContentWarning(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $currentPreferences = (int) $user->getAttribute('websitePrefs');
        $newPreferences = $currentPreferences | (1 << UserPreference::Site_SuppressMatureContentWarning);

        $user->websitePrefs = $newPreferences;
        $user->save();

        return response()->json(['success' => true]);
    }

    // TODO migrate to $user->preferences blob
    public function updatePreferences(UpdateWebsitePrefsRequest $request): JsonResponse
    {
        $data = UpdateWebsitePrefsData::fromRequest($request);

        /** @var User $user */
        $user = $request->user();

        $user->update($data->toArray());

        return response()->json(['success' => true]);
    }

    public function resetWebApiKey(ResetWebApiKeyRequest $request): JsonResponse
    {
        $newKey = generateAPIKey($request->user()->username);

        return response()->json(['newKey' => $newKey]);
    }

    public function resetConnectApiKey(ResetConnectApiKeyRequest $request): JsonResponse
    {
        generateAppToken($request->user()->username, $newToken);

        return response()->json(['success' => true]);
    }
}
