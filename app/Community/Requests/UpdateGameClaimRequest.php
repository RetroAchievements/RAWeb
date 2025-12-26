<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameClaimRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ClaimStatus::class)],
            'special' => ['sometimes', Rule::enum(ClaimSpecial::class)],
            'type' => ['sometimes', Rule::enum(ClaimType::class)],
            'set_type' => ['sometimes', Rule::enum(ClaimSetType::class)],
            'claimed' => 'sometimes|date',
            'finished' => 'sometimes|date',
        ];
    }
}
