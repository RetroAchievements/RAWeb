<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Models\ConnectWarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait GeneratesConnectWarnings
{
    protected ?ConnectWarning $connectWarning = null;

    public function handleRequest(Request $request): JsonResponse
    {
        $result = parent::handleRequest($request);

        if ($this->connectWarning !== null) {
            $this->connectWarning->save();
        }

        return $result;
    }

    protected function addSmell(Request $request, string $smell): void
    {
        if (!$this->connectWarning) {
            $this->connectWarning = new ConnectWarning([
                'method' => $request->input('r'),
                'username' => $request->input('u') ?? '',
                'smells' => $smell,
            ]);
        } else {
            $this->connectWarning->smells .= ',' . $smell;
        }
    }
}
