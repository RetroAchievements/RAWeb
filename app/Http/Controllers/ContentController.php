<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ContentController extends Controller
{
    public function terms(): View
    {
        return view('pages.terms');
    }

    public function demo(): View
    {
        return view('pages.demo');
    }

    public function errorDemo(int $code): View
    {
        if (!view()->exists('errors.' . $code)) {
            throw new NotFoundHttpException();
        }

        return view('errors.' . $code)
            ->with('exception', new Exception('', $code));
    }
}
