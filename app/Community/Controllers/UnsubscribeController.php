<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\UnsubscribeShowPagePropsData;
use App\Http\Controller;
use App\Mail\Services\UnsubscribeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UnsubscribeController extends Controller
{
    public function __construct(
        private UnsubscribeService $unsubscribeService,
    ) {
    }

    public function show(Request $request, string $token): InertiaResponse|Response
    {
        /**
         * @see https://laravel.com/docs/12.x/urls#signed-urls
         */
        if (!$request->hasValidSignature()) {
            // For POST (one-click) requests, return 401 Unauthorized.
            if ($request->isMethod('post')) {
                return response('Unauthorized', 401);
            }

            // For GET requests, show an error page.
            $props = [
                'success' => false,
                'error' => 'expired',
                'descriptionKey' => null,
                'descriptionParams' => null,
                'undoToken' => null,
            ];

            return Inertia::render('unsubscribe', UnsubscribeShowPagePropsData::from($props));
        }

        // Handle POST requests for RFC 8058 one-click unsubscribe from email clients.
        if ($request->isMethod('post')) {
            if ($request->input('List-Unsubscribe') !== 'One-Click') {
                return response('Bad Request', 400);
            }

            // We don't want to generate undo tokens for these.
            $result = $this->unsubscribeService->processUnsubscribe(
                $token,
                shouldGenerateUndoToken: false
            );

            if (!$result['success']) {
                return response('Internal Server Error', 500);
            }

            return response('', 200);
        }

        // Otherwise, handle GET requests for browser-based unsubscribes.
        $result = $this->unsubscribeService->processUnsubscribe($token);

        $props = [
            'success' => false,
            'error' => null,
            'descriptionKey' => null,
            'descriptionParams' => null,
            'undoToken' => null,
        ];

        if (!$result['success']) {
            $props['error'] = $result['errorCode'] ?? 'unknown';
        } else {
            $props['success'] = true;
            $props['descriptionKey'] = $result['descriptionKey'];
            $props['descriptionParams'] = $result['descriptionParams'];
            $props['undoToken'] = $result['undoToken'];
        }

        return Inertia::render('unsubscribe', UnsubscribeShowPagePropsData::from($props));
    }
}
