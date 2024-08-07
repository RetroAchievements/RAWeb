<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        if ($user->isBanned()) {
            return false;
        }

        $isMottoBeingUpdated = $this->input('motto') !== $user->Motto;
        if ($isMottoBeingUpdated && !$user->can('updateMotto', $user)) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'motto' => 'nullable|string|max:50',
            'userWallActive' => 'nullable|boolean',
        ];
    }
}
