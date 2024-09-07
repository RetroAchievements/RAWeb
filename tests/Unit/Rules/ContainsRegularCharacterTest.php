<?php

namespace Tests\Unit\Rules;

use App\Support\Rules\ContainsRegularCharacter;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContainsRegularCharacterTest extends TestCase
{
    public function testItPassesWhenInputContainsRegularCharacters(): void
    {
        $data = ['body' => 'This is a valid comment with letters and symbols!'];

        $validator = Validator::make($data, [
            'body' => ['required', 'string', new ContainsRegularCharacter()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItFailsWhenInputOnlyContainsControlCharacters(): void
    {
        $data = ['body' => "\u{200B}\u{200E}\u{200F}"];

        $validator = Validator::make($data, [
            'body' => ['required', 'string', new ContainsRegularCharacter()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWhenInputIsEmpty(): void
    {
        $data = ['body' => ''];

        $validator = Validator::make($data, [
            'body' => ['required', 'string', new ContainsRegularCharacter()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItPassesWhenInputIsOnlySymbols(): void
    {
        $data = ['body' => '***!!!'];

        $validator = Validator::make($data, [
            'body' => ['required', 'string', new ContainsRegularCharacter()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItFailsWhenInputContainsOnlyHtmlEscapeCodes(): void
    {
        $data = ['body' => '&#x200B;&#x200E;&#x200F;'];

        $validator = Validator::make($data, [
            'body' => ['required', 'string', new ContainsRegularCharacter()],
        ]);

        $this->assertTrue($validator->fails());
    }
}
