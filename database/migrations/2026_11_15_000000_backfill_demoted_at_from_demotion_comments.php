<?php

declare(strict_types=1);

use App\Community\Enums\CommentableType;
use App\Models\Comment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(<<<'SQL'
            UPDATE achievements ach
            INNER JOIN (
                SELECT commentable_id, MAX(created_at) AS last_demotion_at
                FROM comments
                WHERE user_id = ?
                    AND commentable_type = ?
                    AND body LIKE '%demoted this achievement%'
                GROUP BY commentable_id
            ) demotion_comments ON demotion_comments.commentable_id = ach.id
            SET ach.demoted_at = demotion_comments.last_demotion_at
            WHERE ach.is_promoted = 0
                AND ach.demoted_at IS NULL
        SQL, [Comment::SYSTEM_USER_ID, CommentableType::Achievement->value]);
    }

    public function down(): void
    {
        // irreversible
    }
};
