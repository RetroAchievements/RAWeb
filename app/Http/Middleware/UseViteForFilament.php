<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class UseViteForFilament
{
    /**
     * This middleware configures Vite to use Filament-specific build files.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useHotFile('filament.hot')->useBuildDirectory('fi-build');

        return $next($request);
    }
}
