<?php

declare(strict_types=1);

namespace Tests\Feature\Site;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersPages(): void
    {
        // TODO rewrite legacy migrations first
        // $this->get('/')->assertSuccessful();

        $this->get('demo')->assertSuccessful();
    }
}
