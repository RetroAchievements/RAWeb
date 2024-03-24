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
        $system4 = System::factory()->create(['ID' => System::Events]);

        // games with achievements for all systems
        $this->get($this->apiUrl('GetConsoleIDs'))
            ->assertSuccessful()
            ->assertJsonCount(4)
            ->assertJson([
                [
                    'ID' => $system1->ID,
                    'Name' => $system1->Name,
                    'IconURL' => $system1->icon_url,
                    'Active' => true,
                    'IsGameSystem' => true,
                ],
                [
                    'ID' => $system2->ID,
                    'Name' => $system2->Name,
                    'IconURL' => $system2->icon_url,
                    'Active' => true,
                    'IsGameSystem' => true,
                ],
                [
                    'ID' => $system3->ID,
                    'Name' => $system3->Name,
                    'IconURL' => $system3->icon_url,
                    'Active' => true,
                    'IsGameSystem' => true,
                ],
                [
                    'ID' => $system4->ID,
                    'Name' => $system4->Name,
                    'IconURL' => $system4->icon_url,
                    'Active' => true,
                    'IsGameSystem' => false,
                ],
            ]);
    }

    public function testGetConsoleIdsOnlyActive(): void
    {
        /** @var System $system1 */
        $system1 = System::factory()->create();
        /** @var System $system2 */
        $system2 = System::factory()->create(['ID' => System::Hubs]);

        // only active systems
        $this->get($this->apiUrl('GetConsoleIDs', ['a' => 1]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'ID' => $system1->ID,
                    'Name' => $system1->Name,
                    'IconURL' => $system1->icon_url,
                    'Active' => true,
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
                    'Active' => true,
                    'IsGameSystem' => true,
                ],
            ]);
    }
}
