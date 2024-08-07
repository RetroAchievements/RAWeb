<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UpdateEmailData;
use App\Community\Data\UpdatePasswordData;
use App\Community\Data\UpdateProfileData;
use App\Community\Data\UpdateWebsitePrefsData;
use App\Community\Enums\ArticleType;
use App\Community\Requests\ResetConnectApiKeyRequest;
use App\Community\Requests\ResetWebApiKeyRequest;
use App\Community\Requests\UpdateEmailRequest;
use App\Community\Requests\UpdatePasswordRequest;
use App\Community\Requests\UpdateProfileRequest;
use App\Community\Requests\UpdateWebsitePrefsRequest;
use App\Enums\Permissions;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $section = 'profile'): View
    {
        $this->authorize('updateSettings', $section);

        if (!view()->exists("settings.$section")) {
            abort(404, 'Not found');
        }

        return view("settings.$section");
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
            "{$user->username} changed their email address"
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
