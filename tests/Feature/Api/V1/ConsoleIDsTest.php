<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                    'IconURL' => $system1->icon_url,
                    'Active' => boolval($system1->active),
                    'IsGameSystem' => true,
                ],
                [
                    'ID' => $system2->ID,
                    'Name' => $system2->Name,
                    'IconURL' => $system2->icon_url,
                    'Active' => boolval($system2->active),
                    'IsGameSystem' => true,
                ],
                [
                    'ID' => $system3->ID,
                    'Name' => $system3->Name,
                    'IconURL' => $system3->icon_url,
                    'Active' => boolval($system3->active),
                    'IsGameSystem' => true,
                ],
                [
                    'ID' => $system4->ID,
                    'Name' => $system4->Name,
                    'IconURL' => $system4->icon_url,
                    'Active' => boolval($system4->active),
                    'IsGameSystem' => true,
                ],
            ]);
    }

    public function testGetConsoleIdsOnlyActive(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create(['active' => 1]);
        /** @var System $system2 */
        $system2 = System::factory()->create(['active' => 0]);

        // only active systems
        $this->get($this->apiUrl('GetConsoleIDs', ['a' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'ID' => $system1->ID,
                    'Name' => $system1->Name,
                    'IconURL' => $system1->icon_url,
                    'Active' => boolval($system1->active),
                    'IsGameSystem' => true,
                ],
            ]);
    }

    public function testGetConsoleIdsOnlyGameSystems(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create(['ID' => System::Hubs]);

        // only game systems
        $this->get($this->apiUrl('GetConsoleIDs', ['g' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'ID' => $system1->ID,
                    'Name' => $system1->Name,
                    'IconURL' => $system1->icon_url,
                    'Active' => boolval($system1->active),
                    'IsGameSystem' => true,
                ],
            ]);
    }
}
