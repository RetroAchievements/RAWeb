<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketReporterUnlockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'isHardcore' => 'required|boolean',
        ];
    }
}
