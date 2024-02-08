<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait HandlesPublicFileRequests
{
    /**
     * @deprecated Migrate to Model/View/Controllers
     */
    private function handlePageRequest(string $file, ?string $resourceId = null): Response|RedirectResponse|View
    {
        if (is_string($resourceId)) {
            $_GET['ID'] = $resourceId;
        }

        $viewPath = 'pages-legacy.' . str_replace('/', '.', $file);

        if (!\Illuminate\Support\Facades\View::exists($viewPath) || $file === 'index') {
            throw new NotFoundHttpException();
        }

        return view($viewPath);
    }

    /**
     * @deprecated Migrate to Model/View/Controllers
     */
    private function handleRequest(string $path): Response|RedirectResponse|JsonResponse|StreamedResponse
    {
        $scriptPath = public_path("$path.php");
        if (!file_exists($scriptPath) || $scriptPath === 'index') {
            throw new NotFoundHttpException();
        }

        $this->runInterceptor($path);

        $response = require $scriptPath;

        if ($response instanceof Response) {
            return $response;
        }

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        return response($response, headers: ['Content-Type' => 'application/json']);
    }

    private function runInterceptor(string $path): void
    {
        if (config('interceptor.connect') && $path === 'dorequest') {
            if (file_exists(config('interceptor.connect'))) {
                require config('interceptor.connect');
            }
        } elseif (config('interceptor.web')) {
            if (file_exists(config('interceptor.web'))) {
                require config('interceptor.web');
            }
        }
    }
}
