<?php

namespace Tests\Unit\Rules;

use App\Support\Rules\PasswordRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PasswordRulesTest extends TestCase
{
    public function testItRejectsPasswordsLongerThanTheSharedMaximumBeforeExpensiveRulesRun(): void
    {
        $validator = Validator::make([
            'username' => 'Scott',
            'email' => 'scott@example.com',
            'password' => str_repeat('a', 129),
        ], [
            'password' => PasswordRules::get(),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('128 characters', $validator->errors()->first('password'));
    }
}
