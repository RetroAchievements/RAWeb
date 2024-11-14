<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersPageWithEmptyDatabase(): void
    {
        $this->get('/')->assertSuccessful();
    }

    public function testItRendersContactPage(): void
    {
        $this->get('/contact')->assertSuccessful();
    }

    public function testItRendersTermsPage(): void
    {
        $this->get('/terms')->assertSuccessful();
    }
}
