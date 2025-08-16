<?php

declare(strict_types=1);

namespace App\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddContentLengthHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$response->headers->has('Content-Length')) {
            $content = $response->getContent();
            if ($content !== false && $content !== null) {
                $response->headers->set('Content-Length', (string) strlen($content));
            }
        }

        return $response;
    }
}
