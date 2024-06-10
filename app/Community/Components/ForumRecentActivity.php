<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class ForumRecentActivity extends Component
{
    private int $numToFetch = 4;
    private ?User $user = null;

    public function __construct(int $numToFetch = 4)
    {
        $this->user = Auth::user() ?? null;
        $this->numToFetch = $numToFetch;
    }

    public function render(): ?View
    {
        $recentForumPosts = $this->prepareRecentForumPosts(
            $this->numToFetch,
            $this->user?->Permissions ?? Permissions::Unregistered,
            $this->user?->websitePrefs ?? 0,
        );

        return view('components.forum.recent-activity', [
            'recentForumPosts' => $recentForumPosts,
            'userPreferences' => $this->user?->websitePrefs ?? 0,
        ]);
    }

    private function prepareRecentForumPosts(int $numToFetch = 4, int $userPermissions = Permissions::Unregistered, int $userPreferences = 0): array
    {
        $recentForumPosts = [];
        $rawRecentPosts = getRecentForumPosts(0, $numToFetch, 100, $userPermissions);

        if ($rawRecentPosts->isEmpty()) {
            return $recentForumPosts;
        }

        $isShowAbsoluteDatesPreferenceSet = $userPreferences && BitSet($userPreferences, UserPreference::Forum_ShowAbsoluteDates);

        foreach ($rawRecentPosts as $rawRecentPost) {
            $recentForumPosts[] = [
                'ShortMsg' => $this->buildShortMessage($rawRecentPost),
                'Author' => $rawRecentPost['Author'],
                'ForumTopicTitle' => $rawRecentPost['ForumTopicTitle'],
                'HasDateTooltip' => !$isShowAbsoluteDatesPreferenceSet,

                'URL' => route('forum.topic', [
                        'forumTopic' => $rawRecentPost['ForumTopicID'],
                        'comment' => $rawRecentPost['CommentID'],
                    ]) . '#' . $rawRecentPost['CommentID'],

                'PostedAt' => $isShowAbsoluteDatesPreferenceSet
                    ? getNiceDate(strtotime($rawRecentPost['PostedAt']))
                    : Carbon::parse($rawRecentPost['PostedAt'])->diffForHumans(),

                'TitleAttribute' => $isShowAbsoluteDatesPreferenceSet
                    ? null
                    : Carbon::parse($rawRecentPost['PostedAt'])->format('F j Y, g:ia'),
            ];
        }

        return $recentForumPosts;
    }

    private function buildShortMessage(array $rawRecentPost): string
    {
        $shortMsg = trim($rawRecentPost['ShortMsg']);
        $shortMsg = $rawRecentPost['IsTruncated'] ? $shortMsg . "..." : $shortMsg;

        return Shortcode::stripAndClamp($shortMsg);
    }
}
