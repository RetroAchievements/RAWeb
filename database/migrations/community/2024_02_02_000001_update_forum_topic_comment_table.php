<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('ForumTopicComment');

            if (!array_key_exists('forum_topic_comments_author_id_created_at_index', $indexesFound)) {
                $table->index(['AuthorID', 'DateCreated'], 'forum_topic_comments_author_id_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropIndex('forum_topic_comments_author_id_created_at_index');
        });
    }
};
