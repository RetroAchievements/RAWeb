<?php

declare(strict_types=1);

namespace App\Community\Requests;

class UpdateOAuthClientRequest extends StoreOAuthClientRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),

            // A registered application's client type and device flow support are immutable.
            'type' => ['sometimes'],
            'enableDeviceFlow' => ['sometimes'],
        ];
    }
}
