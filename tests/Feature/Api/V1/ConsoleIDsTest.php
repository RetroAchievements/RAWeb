<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Platform\Models\System;
use Tests\TestCase;

class ConsoleIDsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetConsoleIds(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create();
        /** @var System $system3 */
        $system3 = System::factory()->create();
        /** @var System $system4 */
        $system4 = System::factory()->create();

        // games with achievements for all systems
        $this->get($this->apiUrl('GetConsoleIDs'))
            ->assertSuccessful()
            ->assertJsonCount(4)
            ->assertJson([
                [
                    'ID' => $system1->ID,
                    'Name' => $system1->Name,
                ],
                [
                    'ID' => $system2->ID,
                    'Name' => $system2->Name,
                ],
                [
                    'ID' => $system3->ID,
                    'Name' => $system3->Name,
                ],
                [
                    'ID' => $system4->ID,
                    'Name' => $system4->Name,
                ],
            ]);
    }
}
