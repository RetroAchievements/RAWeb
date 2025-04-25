<?php

declare(strict_types=1);

namespace Tests\Feature\Legacy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenderTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersCards(): void
    {
        $this->get('test/cards.php')->assertSuccessful();
    }
}
