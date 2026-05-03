<?php

namespace Tests\Unit\Rules;

use App\Support\Rules\MinimumUniqueCharacters;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MinimumUniqueCharactersTest extends TestCase
{
    public function testItPassesWhenInputContainsEnoughUniqueCharacters(): void
    {
        $validator = Validator::make([
            'password' => 'abcdeabcde',
        ], [
            'password' => [new MinimumUniqueCharacters()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItFailsWhenInputDoesNotContainEnoughUniqueCharacters(): void
    {
        $validator = Validator::make([
            'password' => 'aaaaaaaaaa',
        ], [
            'password' => [new MinimumUniqueCharacters()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItCountsCharactersRatherThanBytesForMultibyteInput(): void
    {
        // 日本語 contributes 3 unique characters but 8 unique bytes.
        $validator = Validator::make([
            'password' => '日本語日本語日本語',
        ], [
            'password' => [new MinimumUniqueCharacters()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItStripsConfiguredWordsBeforeCountingUniqueCharacters(): void
    {
        $validator = Validator::make([
            'password' => 'retroachievementsabc',
        ], [
            'password' => [new MinimumUniqueCharacters(stripWords: ['retroachievements'])],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('common word', $validator->errors()->first('password'));
    }
}
