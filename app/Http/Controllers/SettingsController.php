<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateAvatarAction;
use App\Http\Controller;
use App\Http\Requests\ProfileSettingsRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
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

    /**
     * Update the specified resource in storage.
     */
    public function updateProfile(
        ProfileSettingsRequest $request,
        UpdateAvatarAction $updateAvatarAction
    ): RedirectResponse {
        $this->authorize('updateProfileSettings', $request->user());

        /**
         * settings are always processed in the current user's context
         */
        /** @var User $user */
        $user = $request->user();

        $updateAvatarAction->execute($user, $request);
        $data = $request->validated();
        // $data['wall_active'] = $data['wall_active'] ?? false;
        $user->fill($data)->save();

        // dd($data);

        return back()->with('success', $this->resourceActionSuccessMessage('setting.profile', 'update', null, 2));
    }
}
