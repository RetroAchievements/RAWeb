<?php

declare(strict_types=1);

namespace Tests\Unit\Community\Actions;

use App\Community\Actions\BuildReportContextAction;
use App\Community\Enums\DiscordReportableType;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildReportContextActionTest extends TestCase
{
    use RefreshDatabase;

    private BuildReportContextAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new BuildReportContextAction();
    }

    public function testItBuildsForumTopicCommentContextForInbox(): void
    {
        // Arrange
        $author = User::factory()->create(['User' => 'ReportedUser']);
        $topic = ForumTopic::factory()->create();
        $comment = ForumTopicComment::factory()->create([
            'body' => 'This is the reported comment content.',
            'author_id' => $author->id,
            'forum_topic_id' => $topic->id,
        ]);
        $comment->load('forumTopic');

        $userMessage = 'This comment violates the rules.';

        // Act
        $result = $this->action->execute(
            $userMessage,
            DiscordReportableType::ForumTopicComment,
            $comment->id,
            forDiscord: false // !!
        );

        // Assert
        $this->assertStringContainsString('[b]Reported Content:[/b]', $result);
        $this->assertStringContainsString('[url=', $result);
        $this->assertStringContainsString('[b]Author:[/b] [user=' . $author->id . ']', $result);
        $this->assertStringContainsString('[b]Posted:[/b]', $result);
        $this->assertStringContainsString('[b]Report Details:[/b]', $result);
        $this->assertStringContainsString($userMessage, $result);

        $this->assertStringNotContainsString('**', $result); // no markdown
    }

    public function testItBuildsForumTopicCommentContextForDiscord(): void
    {
        // Arrange
        $author = User::factory()->create(['User' => 'ReportedUser', 'display_name' => 'Reported User']);
        $topic = ForumTopic::factory()->create();
        $comment = ForumTopicComment::factory()->create([
            'body' => 'This is the reported comment content.',
            'author_id' => $author->id,
            'forum_topic_id' => $topic->id,
        ]);
        $comment->load('forumTopic');

        $userMessage = 'This comment violates the rules.';

        // Act
        $result = $this->action->execute(
            $userMessage,
            DiscordReportableType::ForumTopicComment,
            $comment->id,
            forDiscord: true // !!
        );

        // Assert
        $this->assertStringContainsString('**Reported Content:**', $result);
        $this->assertStringContainsString('**Author:** [Reported User](', $result);

        $this->assertStringContainsString('**Posted:** <t:', $result); // discord timestamp format
        $this->assertStringContainsString(':R>', $result);

        $this->assertStringContainsString('**Excerpt:**', $result);
        $this->assertStringContainsString('**Report Details:**', $result);
        $this->assertStringContainsString($userMessage, $result);
    }

    public function testItBuildsDirectMessageContextForInbox(): void
    {
        // Arrange
        $author = User::factory()->create(['User' => 'MessageSender']);
        $message = Message::factory()->create([
            'body' => 'This is an inappropriate direct message.',
            'author_id' => $author->id,
        ]);

        $userMessage = 'This DM is harassing me.';

        // Act
        $result = $this->action->execute(
            $userMessage,
            DiscordReportableType::DirectMessage,
            $message->id,
            forDiscord: false // !!
        );

        // Assert
        $this->assertStringNotContainsString('[b]Reported Content:[/b]', $result); // no header for DMs
        $this->assertStringContainsString('[b]Author:[/b] [user=' . $author->id . ']', $result);
        $this->assertStringContainsString('[b]Sent:[/b]', $result); // !! "Sent", not "Posted", for DMs
        $this->assertStringContainsString('[b]Report Details:[/b]', $result);
        $this->assertStringContainsString($userMessage, $result);
    }

    public function testItBuildsDirectMessageContextForDiscord(): void
    {
        // Arrange
        $author = User::factory()->create(['User' => 'MessageSender', 'display_name' => 'Message Sender']);
        $message = Message::factory()->create([
            'body' => 'This is an inappropriate direct message.',
            'author_id' => $author->id,
        ]);

        $userMessage = 'This DM is harassing me.';

        // Act
        $result = $this->action->execute(
            $userMessage,
            DiscordReportableType::DirectMessage,
            $message->id,
            forDiscord: true // !!
        );

        // Assert
        $this->assertStringNotContainsString('**Reported Content:**', $result); // no header for DMs
        $this->assertStringContainsString('**Author:** [Message Sender](', $result);
        $this->assertStringContainsString('**Sent:** <t:', $result); // "Sent", not "Posted", for DMs

        $this->assertStringContainsString('**Full Message:**', $result); // full message for DMs in Discord

        $this->assertStringContainsString('This is an inappropriate direct message.', $result);
        $this->assertStringContainsString('**Report Details:**', $result);
        $this->assertStringContainsString($userMessage, $result);
    }
}
