<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Spatie\RobotsMiddleware\RobotsMiddleware as SpatieRobotsMiddleware;

class RobotsMiddleware extends SpatieRobotsMiddleware
{
    protected function shouldIndex(Request $request)
    {
        /*
         * do not index anything when not in production
         */
        if (!app()->environment('production')) {
            return false;
        }

        /*
         * TODO: add all routes that should not be indexed
         */
        // return $request->segment(1) !== 'admin';
        return true;
    }
}
