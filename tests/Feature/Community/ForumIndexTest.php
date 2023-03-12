<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumIndexTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersListWithEmptyDatabase(): void
    {
        $this->get('forum.php')->assertSuccessful();
    }
}
