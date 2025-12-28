<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Concerns;

use App\Community\Enums\CommentableType;
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
            'ID' => Comment::SYSTEM_USER_ID,
            'User' => 'Server',
            'Permissions' => Permissions::Unregistered,
            'EmailAddress' => '',
            'email_verified_at' => null,
            'Password' => null,
            'RAPoints' => 0,
            'APIKey' => null,
            'remember_token' => null,
        ]);
    }

    protected function assertAuditComment(CommentableType $commentableType, int $commentableId, string $message, ?Carbon $when = null): void
    {
        $foundDate = null;
        $comments = Comment::where('commentable_type', $commentableType)
            ->where('commentable_id', $commentableId)
            ->where('user_id', $this->serverUser->ID);
        foreach ($comments->get() as $comment) {
            if ($comment->body === $message) {
                if ($when === null || $when->eq($comment->created_at)) {
                    return;
                }
                $foundDate = $comment->created_at;
            }
        }

        if ($comments->count() === 1) {
            $this->assertEquals($message, $comments->first()->body);
        } elseif ($foundDate !== null) {
            $this->fail("Date mismatch for audit comment. Expected: {$when->toDateTimeString()}, found {$foundDate->toDateTimeString()}");
        } else {
            $this->fail("No audit comment found matching: $message");
        }
    }
}
