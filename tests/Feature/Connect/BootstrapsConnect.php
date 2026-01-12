<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

trait BootstrapsConnect
{
    use TestsConnect;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createConnectUser();
    }
}
