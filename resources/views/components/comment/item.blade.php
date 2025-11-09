@props([
    'author' => null, // ?User
    'when' => null, // ?Carbon
    'payload' => '',
    'articleType' => 0,
    'articleId' => 0,
    'commentId' => 0,
    'allowDelete' => false,
])

@php

use App\Community\Actions\FormatLegacyCommentPayloadAction;
use App\Community\Enums\ArticleType;
use Illuminate\Support\Facades\Auth;

settype($articleType, 'integer');

$commentTypeLabel = match ($articleType) {
    ArticleType::Game => 'Game Wall Comment',
    ArticleType::Achievement => 'Achievement Wall Comment',
    ArticleType::User => 'User Wall Comment',
    ArticleType::Leaderboard => 'Leaderboard Comment',
    ArticleType::AchievementTicket => 'Ticket Comment',
    default => 'Wall Comment',
};

$nonReportableArticleTypes = [
    ArticleType::News,
    ArticleType::UserModeration,
    ArticleType::GameHash,
    ArticleType::SetClaim,
    ArticleType::GameModification,
];

$userModel = Auth::user();
$canCreateModerationReports = (
    $userModel->can('createModerationReports', $userModel)
    && $author
    && $author->id !== auth()->user()->id
    && $author->User !== 'Server'
    && !in_array($articleType, $nonReportableArticleTypes)
);

@endphp

@if ($author && $author->User === 'Server')
    <tr class="comment system">
        <td class="align-top py-2">
            @if ($commentId > 0)
                <div class="relative">
                    <div class="absolute h-px w-px left-0" style="top: -74px;" id="comment_{{ $commentId }}"></div>
                </div>
            @endif
        </td>
        <td class="w-full py-2" colspan="3">
            <div>
                <span class="smalldate">{{ $when?->format('j M Y H:i') }}</span>
            </div>

            <div style="word-break: break-word">
                {!! $payload !!}
            </div>
        </td>
    </tr>
@elseif ($articleType !== ArticleType::AchievementTicket &&
         $author && $author->banned_at && !request()->user()?->can('manage', $author))
    {{-- banned user comments are only visible to moderators --}}
@else
    <tr class="comment group" @if ($commentId > 0) id="comment_{{ $commentId }}_highlight" @endif>
        <td class="align-top py-2">
            @if ($commentId > 0)
                <div class="relative">
                    <div class="absolute h-px w-px left-0" style="top: -74px;" id="comment_{{ $commentId }}"></div>
                </div>
            @endif
            
            {!! userAvatar($author ?? 'Deleted User', label: false) !!}
        </td>

        <td class="w-full py-2" colspan="3">
            @if ($allowDelete || $canCreateModerationReports)
                <div style="float: right;">
                    @if ($canCreateModerationReports)
                        <a
                            href="{{ route('message-thread.create', [
                                'to' => 'RAdmin',
                                'subject' => 'Report: ' . $commentTypeLabel . ' by ' . ($author->display_name ?? 'Deleted User'),
                                'rType' => 'Comment',
                                'rId' => $commentId
                            ]) }}"
                            aria-label="Report comment"
                            title="Report comment"
                        >
                            <x-fas-flag class="text-yellow-600 size-3 sm:hidden" />
                        </a>
                    @endif

                    @if ($allowDelete)
                        <a onclick="removeComment({{ $articleType }}, {{ $articleId }}, {{ $commentId }}); return false;" href="#" aria-label="Delete comment" title="Delete comment">
                            <x-fas-xmark class="text-red-600 h-5 w-5" />
                        </a>
                    @endif
                </div>
            @endif

            <div>
                {!! userAvatar($author ?? 'Deleted User', label: true) !!}

                <span class="smalldate">{{ $when?->format('j M Y H:i') }}</span>

                @if ($canCreateModerationReports)
                    <a
                        href="{{ route('message-thread.create', [
                            'to' => 'RAdmin',
                            'subject' => 'Report: ' . $commentTypeLabel . ' by ' . ($author->display_name ?? 'Deleted User'),
                            'rType' => 'Comment',
                            'rId' => $commentId
                        ]) }}"
                        aria-label="Report comment"
                        title="Report comment"
                    >
                        <x-fas-flag class="text-yellow-600 size-3 hidden sm:inline ml-0.5 opacity-0 group-hover:opacity-100" />
                    </a>
                @endif
            </div>

            {{-- Specially handle newlines in comments, render everything else as plain text. --}}
            @php
                $formattedPayload = (new FormatLegacyCommentPayloadAction())->execute(
                    $payload,
                    isTicketComment: (int) $articleType === ArticleType::AchievementTicket,
                );
            @endphp
            <div style="word-break: break-word;">
                {!! $formattedPayload !!}
            </div>
        </td>
    </tr>
@endif