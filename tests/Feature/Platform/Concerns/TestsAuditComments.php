<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Concerns;

use App\Enums\Permissions;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Carbon;

trait TestsAuditComments
{
    private User $serverUser;

    protected function addServerUser(): void
    {
        $this->serverUser = User::factory()->create([
            'id' => Comment::SYSTEM_USER_ID,
            'username' => 'Server',
            'Permissions' => Permissions::Unregistered,
            'email' => '',
            'email_verified_at' => null,
            'password' => null,
            'points' => 0,
            'web_api_key' => null,
            'remember_token' => null,
        ]);
    }

    protected function assertAuditComment(int $articleType, int $articleID, string $message, ?Carbon $when = null): void
    {
        $foundDate = null;
        $comments = Comment::where('ArticleType', $articleType)
            ->where('ArticleID', $articleID)
            ->where('user_id', $this->serverUser->id);
        foreach ($comments->get() as $comment) {
            if ($comment->Payload === $message) {
                if ($when === null || $when === $comment->Submitted) {
                    return;
                }
                $foundDate = $comment->Submitted;
            }
        }

        if ($comments->count() === 1) {
            $this->assertEquals($message, $comments->first()->Payload);
        } elseif ($foundDate !== null) {
            $this->fail("Date mismatch for audit comment. Expected: {$when->toDateTimeString()}, found {$foundDate->toDateTimeString()}");
        } else {
            $this->fail("No audit comment found matching: $message");
        }
    }
}
