<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @deprecated do not redirect in views, refactor to controller when needed
 */
class ViewRedirect extends RuntimeException
{
    public function __construct(public RedirectResponse $redirect)
    {
        parent::__construct();
    }
}
