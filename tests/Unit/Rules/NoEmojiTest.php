<?php

namespace Tests\Unit\Rules;

use App\Support\Rules\NoEmoji;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class NoEmojiTest extends TestCase
{
    public function testItPassesWithRegularText(): void
    {
        $data = ['body' => 'This is a regular text string with punctuation!'];

        $validator = Validator::make($data, [
            'body' => [new NoEmoji()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItPassesWithBracketsAndNonEmojiSpecialChars(): void
    {
        $data = ['body' => '[Hacks - Ninja Gaiden 2©]'];

        $validator = Validator::make($data, [
            'body' => [new NoEmoji()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItFailsWithBasicEmoji(): void
    {
        $data = ['body' => 'Hello 👋 World'];

        $validator = Validator::make($data, [
            'body' => [new NoEmoji()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithComplexEmojiUsingToneModifiers(): void
    {
        $data = ['body' => 'Hello 👋🏽 World'];

        $validator = Validator::make($data, [
            'body' => [new NoEmoji()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithCompoundEmoji(): void
    {
        $data = ['body' => 'Family 👨‍👩‍👧‍👦'];

        $validator = Validator::make($data, [
            'body' => [new NoEmoji()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItPassesWithAnEmptyString(): void
    {
        $data = ['body' => ''];

        $validator = Validator::make($data, [
            'body' => [new NoEmoji()],
        ]);

        $this->assertFalse($validator->fails());
    }
}
