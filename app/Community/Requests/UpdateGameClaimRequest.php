<?php

declare(strict_types=1);

namespace App\Community\Requests;

use App\Community\Enums\ArticleType;
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
            'status' => ['sometimes', 'integer', Rule::in(ClaimStatus::cases())],
            'special' => ['sometimes', 'integer', Rule::in(ClaimSpecial::cases())],
            'type' => ['sometimes', 'integer', Rule::in(ClaimType::cases())],
            'set_type' => ['sometimes', 'integer', Rule::in(ClaimSetType::cases())],
            'claimed' => 'sometimes|date',
            'finished' => 'sometimes|date',        
        ];
    }
}
