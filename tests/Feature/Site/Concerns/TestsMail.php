<?php

declare(strict_types=1);

namespace Tests\Feature\Site\Concerns;

use App\Site\Models\User;
use Illuminate\Support\Facades\Cache;

trait TestsMail
{
    protected function captureEmails()
    {
        // store an empty array in the immediate cache to be populated by mail_utf8().
        // also, clears out the array between tests.
        $emails = Cache::store('array')->put('test:emails', []);
    }

    protected function assertEmailSent(User $user, string $subject, string $body = null)
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

    protected function assertEmailNotSent(User $user, string $subject = null, string $body = null)
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
