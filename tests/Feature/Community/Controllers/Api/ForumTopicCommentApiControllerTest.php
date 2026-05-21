<?php

declare(strict_types=1);

use App\Models\ForumTopic;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
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
