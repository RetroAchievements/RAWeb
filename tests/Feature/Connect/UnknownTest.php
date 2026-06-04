<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::now()->startOfSecond());

    $this->createConnectUser();
});

describe('validation', function () {
    test('unknown request returns error', function () {
        $this->get('dorequest.php?r=unknown')
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Error' => 'Unknown request: unknown',
            ]);
    });

    test('empty r parameter returns error', function () {
        $this->get('dorequest.php?r=&u=' . $this->user->username)
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'invalid_parameter',
                'Error' => 'Unknown request: [null]',
            ]);
    });

    test('missing r parameter returns error', function () {
        $this->get('dorequest.php?u=' . $this->user->username)
            ->assertStatus(422)
            ->assertExactJson([
                'Success' => false,
                'Status' => 422,
                'Code' => 'missing_parameter',
                'Error' => 'One or more required parameters is missing.',
            ]);
    });
});
