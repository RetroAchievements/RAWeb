<?php

declare(strict_types=1);

namespace App\Support\Settings;

use Spatie\Valuestore\Valuestore;

/**
 * @mixin Valuestore
 */
class Settings extends Valuestore
{
    public function __construct()
    {
        parent::__construct();
        $this->setFileName(storage_path('app/settings.json'));
    }
}
