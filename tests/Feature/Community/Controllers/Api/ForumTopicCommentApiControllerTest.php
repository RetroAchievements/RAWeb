<?php

declare(strict_types=1);

use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RolesTableSeeder::class);
});

it('given a regular user posts on a restricted topic, returns a 403', function () {
    // ARRANGE
    $regularUser = User::factory()->create(['email_verified_at' => now()]);
    actingAs($regularUser);

    $topic = ForumTopic::factory()->create([
        'comment_role_id' => Role::findByName(Role::SET_DESIGNER)->id,
    ]);

    // ACT
    $response = postJson(
        route('api.forum-topic-comment.create', ['topic' => $topic]),
        ['body' => 'hello world'],
    );

    // ASSERT
    $response->assertForbidden();
});

it('given a post is attributed to a whitelisted team account, succeeds and auto-authorizes the comment', function () {
    // ARRANGE
    $setDesigner = User::factory()->create(['email_verified_at' => now()]);
    $setDesigner->assignRole(Role::SET_DESIGNER);
    actingAs($setDesigner);

    $setDesignersAccount = User::factory()->create([
        'username' => 'SetDesigners',
        'ManuallyVerified' => false,
    ]);
    $setDesignersAccount->assignRole(Role::SET_DESIGNER);

    $topic = ForumTopic::factory()->create([
        'comment_role_id' => Role::findByName(Role::SET_DESIGNER)->id,
    ]);

    // ACT
    $response = postJson(
        route('api.forum-topic-comment.create', ['topic' => $topic]),
        ['body' => 'announcement', 'postAsUserId' => (string) $setDesignersAccount->id],
    );

    // ASSERT
    $response->assertOk();
    $response->assertJson(['success' => true]);

    assertDatabaseHas('forum_topic_comments', [
        'forum_topic_id' => $topic->id,
        'author_id' => $setDesignersAccount->id,
        'sent_by_id' => $setDesigner->id,
        'is_authorized' => 1, // !!
    ]);
});

it('given an author edits their own post, records edited_at and leaves edited_by_id alone', function () {
    // ARRANGE
    $author = User::factory()->create(['email_verified_at' => now()]);
    actingAs($author);

    $topic = ForumTopic::factory()->create();
    $comment = ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'author_id' => $author->id,
        'body' => 'original text',
    ]);

    // ACT
    $response = patchJson(
        route('api.forum-topic-comment.update', ['comment' => $comment]),
        ['body' => 'revised text'],
    );

    // ASSERT
    $response->assertOk();

    $comment->refresh();
    expect($comment->body)->toEqual('revised text');
    expect($comment->edited_at)->not->toBeNull();
    expect($comment->edited_by_id)->toBeNull();
});

it("given a moderator edits another user's post, records both edited_at and edited_by_id", function () {
    // ARRANGE
    $author = User::factory()->create(['email_verified_at' => now()]);
    $moderator = User::factory()->create(['email_verified_at' => now()]);
    $moderator->assignRole(Role::MODERATOR);
    actingAs($moderator);

    $topic = ForumTopic::factory()->create();
    $comment = ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'author_id' => $author->id,
        'body' => 'original text',
    ]);

    // ACT
    $response = patchJson(
        route('api.forum-topic-comment.update', ['comment' => $comment]),
        ['body' => 'moderated text'],
    );

    // ASSERT
    $response->assertOk();

    $comment->refresh();
    expect($comment->edited_at)->not->toBeNull();
    expect($comment->edited_by_id)->toEqual($moderator->id);
});

it('given the submitted body is unchanged, does not record an edit', function () {
    // ARRANGE
    $author = User::factory()->create(['email_verified_at' => now()]);
    actingAs($author);

    $topic = ForumTopic::factory()->create();
    $comment = ForumTopicComment::factory()->create([
        'forum_topic_id' => $topic->id,
        'author_id' => $author->id,
        'body' => 'original text',
    ]);

    // ACT
    $response = patchJson(
        route('api.forum-topic-comment.update', ['comment' => $comment]),
        ['body' => 'original text'],
    );

    // ASSERT
    $response->assertOk();

    $comment->refresh();
    expect($comment->edited_at)->toBeNull();
});
