<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Models\User;
use App\Support\Rules\LocaleExists;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->user();

        if ($user->isBanned()) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => [
                'string',
                new LocaleExists(),
            ],
        ];
    }
}
