<?php

declare(strict_types=1);

namespace App\Site\Controllers;

use App\Http\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentController extends Controller
{
    // public function home(): View
    // {
    //     return view('home');
    // }

    // public function docs(): View
    // {
    //     return view('docs');
    // }

    public function terms(): View
    {
        return view('terms');
    }

    public function demo(): View
    {
        return view('demo');
    }

    public function errorDemo(int $code): View
    {
        if (!view()->exists('errors.' . $code)) {
            throw new NotFoundHttpException();
        }

        return view('errors.' . $code)
            ->with('exception', new Exception('', $code));
    }

    public function download(): View
    {
        $emulators = getActiveEmulatorReleases();
        usort($emulators, function ($a, $b) {
            return strcasecmp($a['handle'], $b['handle']);
        });

        foreach ($emulators as &$emulator) {
            sort($emulator['systems']);
        }

        return view('download', ['emulators' => $emulators]);
    }
}
