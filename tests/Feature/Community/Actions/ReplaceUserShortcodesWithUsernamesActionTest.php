<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\ReplaceUserShortcodesWithUsernamesAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplaceUserShortcodesWithUsernamesActionTest extends TestCase
{
    use RefreshDatabase;

    public function testItReplacesUserIds(): void
    {
        // Arrange
        User::factory()->create(['id' => 100, 'username' => 'Scott']);
        User::factory()->create(['id' => 101, 'username' => 'Batman']);

        $messageBody = "[user=100] might actually be [user=101].";

        // Act
        $result = (new ReplaceUserShortcodesWithUsernamesAction())->execute($messageBody);

        // Assert
        $this->assertEquals("[user=Scott] might actually be [user=Batman].", $result);
    }
}
