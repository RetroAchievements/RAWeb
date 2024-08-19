<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ResetConnectApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        return $user->can('manipulateApiKeys', $user);
    }

    public function rules(): array
    {
        return [];
    }
}
