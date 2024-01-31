<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIndexTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersListWithEmptyDatabase(): void
    {
        $this->get('userList.php')->assertSuccessful();
    }
}
