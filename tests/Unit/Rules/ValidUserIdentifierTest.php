<?php

namespace Tests\Unit\Rules;

use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidUserIdentifierTest extends TestCase
{
    public function testItPassesWithValidUsername(): void
    {
        $data = ['username' => 'Scott'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItPassesWithMinLengthUsername(): void
    {
        $data = ['username' => 'ab'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItPassesWithMaxLengthUsername(): void
    {
        $data = ['username' => 'abcdefghijklmnopqrst'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItPassesWithValidUlid(): void
    {
        $data = ['username' => '01H9XY7HSB10SCDZ6PZ6T7YQ4A'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertFalse($validator->fails());
    }

    public function testItFailsWithUsernameTooShort(): void
    {
        $data = ['username' => 'a'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithUsernameTooLong(): void
    {
        $data = ['username' => 'abcdefghijklmnopqrstu'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithSpecialCharacters(): void
    {
        $data = ['username' => 'user@name'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithSpaces(): void
    {
        $data = ['username' => 'user name'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithInvalidUlidLength(): void
    {
        $data = ['username' => '01H9XY7HSB10SCDZ6PZ6T7YQ4'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function testItFailsWithInvalidUlidCharacters(): void
    {
        $data = ['username' => '01H9XY7HSB10SCDZ6PZ6T7YQ4@'];

        $validator = Validator::make($data, [
            'username' => [new ValidUserIdentifier()],
        ]);

        $this->assertTrue($validator->fails());
    }
}
