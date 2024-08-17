<?php

declare(strict_types=1);

namespace App\Community\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

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
                if (!Hash::check($value, $user->Password)) {
                    $fail(__('legacy.error.credentials'));
                }
            }],
            'newPassword' => 'required|min:8',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $newPassword = $this->input('newPassword');

            if ($newPassword === $user->username) {
                $validator->errors()->add('newPassword', 'Your password must be different from your username.');
            }
        });
    }
}
