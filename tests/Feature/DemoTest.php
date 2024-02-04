<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersPage(): void
    {
        $this->get('demo')->assertSuccessful();
    }
}
