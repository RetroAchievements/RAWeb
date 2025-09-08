<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UnsubscribeShowPagePropsData;
use App\Http\Controller;
use App\Mail\Services\UnsubscribeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UnsubscribeController extends Controller
{
    public function __construct(
        private UnsubscribeService $unsubscribeService
    ) {
    }

    public function show(Request $request, string $token): InertiaResponse
    {
        $props = [
            'success' => false,
            'error' => null,
            'descriptionKey' => null,
            'descriptionParams' => null,
            'undoToken' => null,
        ];

        /**
         * @see https://laravel.com/docs/12.x/urls#signed-urls
         */
        if (!$request->hasValidSignature()) {
            $props['error'] = 'expired';
        } else {
            $result = $this->unsubscribeService->processUnsubscribe($token);

            if (!$result['success']) {
                $props['error'] = $result['errorCode'] ?? 'unknown';
            } else {
                $props['success'] = true;
                $props['descriptionKey'] = $result['descriptionKey'];
                $props['descriptionParams'] = $result['descriptionParams'];
                $props['undoToken'] = $result['undoToken'];
            }
        }

        return Inertia::render('unsubscribe', UnsubscribeShowPagePropsData::from($props));
    }
}
