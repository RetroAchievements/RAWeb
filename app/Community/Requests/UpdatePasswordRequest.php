<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Support\Rules\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currentPassword' => ['required', function ($attribute, $value, $fail) {
                $user = $this->user();
                if (!Hash::check($value, $user->password)) {
                    $fail(__('legacy.error.credentials'));
                }
            }],
            'newPassword' => PasswordRules::get(),
        ];
    }

    protected function prepareForValidation(): void
    {
        // PasswordRules expects 'username' and 'email' for the different:* checks.
        $this->merge([
            'username' => $this->user()->username,
            'email' => $this->user()->email,
        ]);
    }
}
