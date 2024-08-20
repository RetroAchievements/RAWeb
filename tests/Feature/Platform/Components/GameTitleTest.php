<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Components;

use Tests\TestCase;

class GameTitleTest extends TestCase
{
    public function testItRendersSimpleGameTitle(): void
    {
        $view = $this->blade('<x-game-title rawTitle="Hello, world!" />');

        $view->assertSeeText("Hello, world!");
    }

    public function testItRendersTagsCorrectly(): void
    {
        $view = $this->blade('<x-game-title rawTitle="~Hack~ Hello, world!" />');

        $view->assertSeeTextInOrder(['Hello, world!', 'Hack']);
    }

    public function testItDoesntEncodeSpecialCharacters(): void
    {
        $view = $this->blade('<x-game-title rawTitle="Link\'s Awakening" />');

        $view->assertSeeText("Link's Awakening");
    }

    public function testItRendersSubsetLabelsCorrectly(): void
    {
        $view = $this->blade('<x-game-title rawTitle="~Prototype~ Hello, world! [Subset - Bonus]" />');

        $view->assertSeeTextInOrder([
            'Hello, world!',
            'Prototype',
            'Subset',
            'Bonus',
        ]);
    }

    public function testItCanOptionallyRenderNoTags(): void
    {
        $view = $this->blade('
            <x-game-title
                rawTitle="~Prototype~ Hello, world! [Subset - Bonus]"
                :showTags="$showTags"
            />', [
            'showTags' => false,
        ]
        );

        $view->assertSeeText("Hello, world!");
        $view->assertDontSeeText("Prototype");
        $view->assertDontSeeText("Subset");
        $view->assertDontSeeText("Bonus");
    }
}
