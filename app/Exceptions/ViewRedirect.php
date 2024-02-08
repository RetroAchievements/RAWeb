<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @deprecated TODO do not redirect in views, refactor to controller when needed
 */
class ViewRedirect extends HttpException
{
    public function __construct(
        public RedirectResponse $redirect
    ) {
    }
}
