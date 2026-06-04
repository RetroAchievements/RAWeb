<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use Illuminate\Http\Request;

class UnknownRequestAction extends BaseApiAction
{
    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['r'])) {
            return $this->missingParameters();
        }

        $r = $request->input('r') ?? '[null]';

        return $this->invalidParameter("Unknown request: $r");
    }

    protected function process(): array
    {
        // should never reach here
        return ['Success' => false];
    }
}
