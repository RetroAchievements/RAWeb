<?php

declare(strict_types=1);

namespace Tests\Feature\Concerns;

use App\Models\Emulator;
use App\Models\EmulatorUserAgent;

trait TestsEmulatorUserAgent
{
    protected string $userAgentValid = "MyClient/1.5";
    protected string $userAgentOutdated = "MyClient/1.2";
    protected string $userAgentBlocked = "MyClient/1.0";
    protected string $userAgentUnknown = "OtherClient/1.0";

    protected function seedEmulatorUserAgents(): void
    {
        EmulatorUserAgent::create([
            'emulator_id' => Emulator::create(['name' => 'Test Client'])->id,
            'client' => 'MyClient',
            'minimum_allowed_version' => '1.2',
            'minimum_hardcore_version' => '1.5',
        ]);
    }

    protected function captureEmails(): void
    {
        // store an empty array in the immediate cache to be populated by mail_utf8().
        // also, clears out the array between tests.
        $emails = Cache::store('array')->put('test:emails', []);
    }

    protected function assertEmailSent(User $user, string $subject, ?string $body = null): void
    {
        $emails = Cache::store('array')->get('test:emails') ?? [];
        $matchUser = null;
        $matchSubject = null;
        $matchBoth = null;
        foreach ($emails as $email) {
            if ($email['to'] === $user->EmailAddress && $email['subject'] === $subject) {
                if ($body === null || $body === $email['message']) {
                    return;
                }

                $matchBoth = $email;
            } elseif ($email['to'] === $user->EmailAddress) {
                $matchUser = $email;
            } elseif ($email['subject'] === $subject) {
                $matchSubject = $email;
            }
        }

        $expected = [
            'to' => $user->EmailAddress,
            'subject' => $subject,
            'message' => $body,
        ];

        if ($matchBoth) {
            $this->assertEquals($expected, $matchBoth);
        } elseif ($matchUser) {
            $this->assertEquals($expected, $matchUser);
        } elseif ($matchSubject) {
            $this->assertEquals($expected, $matchSubject);
        } else {
            $this->fail("Expected email sent to {$user->EmailAddress}. No emails captured.");
        }
    }

    protected function assertEmailNotSent(User $user, ?string $subject = null, ?string $body = null): void
    {
        $emails = Cache::store('array')->get('test:emails') ?? [];
        $matchUser = null;
        $matchUserAndSubject = null;
        $matchAll = null;
        foreach ($emails as $email) {
            if ($email['to'] === $user->EmailAddress) {
                if ($subject !== null && $subject === $email['subject']) {
                    if ($body !== null && $body === $email['message']) {
                        $matchAll = $email;
                    } else {
                        $matchUserAndSubject = $email;
                    }
                } else {
                    $matchUser = $email;
                }
            }
        }

        $expected = [
            'to' => $user->EmailAddress,
            'subject' => $subject,
            'message' => $body,
        ];

        if ($matchAll) {
            $this->fail("Found email sent to {$user->EmailAddress} with specified subject and body");
        } elseif ($matchUserAndSubject) {
            $this->fail("Found email sent to {$user->EmailAddress} with specified subject");
        } elseif ($matchUser) {
            $this->fail("Found email sent to {$user->EmailAddress}");
        }
    }
}
