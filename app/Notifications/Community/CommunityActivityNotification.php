<?php

declare(strict_types=1);

namespace App\Notifications\Community;

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommunityActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private ?Achievement $ticketable = null;
    private ?Game $game = null;

    public function __construct(
        private int $activityId,
        private string $activityCommenterDisplayName,
        private CommentableType $commentableType,
        private string $articleTitle,
        private string $urlTarget,
        private bool $isThreadInvolved = false,
        private ?string $payload = null,
    ) {
        // If this is a ticket comment, fetch the ticket data in constructor.
        if ($this->commentableType === CommentableType::AchievementTicket) {
            $ticket = Ticket::with(['achievement.game.system'])->find($this->activityId);
            if ($ticket) {
                $this->ticketable = $ticket->achievement;
                $this->game = $ticket->achievement?->game;
            }
        }
    }

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $unsubscribeData = $this->setupUnsubscribeLinks($notifiable);

        $unsubscribeUrl = $unsubscribeData['granularUrl'] ?? $unsubscribeData['categoryUrl'];

        return (new MailMessage())
            ->subject($this->buildEmailSubject())
            ->markdown('mail.community.activity', [
                'toUser' => $notifiable,
                'toUserDisplayName' => $notifiable->display_name,
                'activityCommenterDisplayName' => $this->activityCommenterDisplayName,
                'commentableType' => $this->commentableType,
                'activityDescription' => $this->buildActivityDescription($notifiable->display_name),
                'urlTarget' => $this->urlTarget,
                'payload' => $this->payload,
                'ticketable' => $this->ticketable,
                'game' => $this->game,
                'granularUrl' => $unsubscribeData['granularUrl'],
                'granularText' => $unsubscribeData['granularText'],
                'categoryUrl' => $unsubscribeData['categoryUrl'],
                'categoryText' => $unsubscribeData['categoryText'],
            ])
            ->withSymfonyMessage(function ($message) use ($unsubscribeUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }

    private function buildActivityDescription(string $toUserDisplayName): string
    {
        return match ($this->commentableType) {
            CommentableType::Game => "the game wall for {$this->articleTitle}",
            CommentableType::Achievement => "the achievement wall for {$this->articleTitle}",
            CommentableType::User => $this->articleTitle === $toUserDisplayName
                ? "your user wall"
                : "{$this->articleTitle}'s user wall",
            CommentableType::Leaderboard => "the leaderboard wall for {$this->articleTitle}",
            CommentableType::Forum => "the forum thread \"{$this->articleTitle}\"",
            CommentableType::AchievementTicket => $this->isThreadInvolved
                ? "a ticket you're subscribed to"
                : "the ticket you reported",
            default => $this->isThreadInvolved ? "a thread you've commented in" : "your latest activity",
        };
    }

    private function buildEmailSubject(): string
    {
        return match ($this->commentableType) {
            CommentableType::Game => "New Game Wall Comment from {$this->activityCommenterDisplayName}",
            CommentableType::Achievement => "New Achievement Comment from {$this->activityCommenterDisplayName}",
            CommentableType::User => "New User Wall Comment from {$this->activityCommenterDisplayName}",
            CommentableType::Leaderboard => "New Leaderboard Comment from {$this->activityCommenterDisplayName}",
            CommentableType::Forum => "New Forum Comment from {$this->activityCommenterDisplayName}",
            CommentableType::AchievementTicket => "New Ticket Comment from {$this->activityCommenterDisplayName}",
            default => "New Activity Comment from {$this->activityCommenterDisplayName}",
        };
    }

    /**
     * @return array{granularUrl: ?string, granularText: ?string, categoryUrl: string, categoryText: string}
     */
    private function setupUnsubscribeLinks(User $notifiable): array
    {
        $unsubscribeService = app(UnsubscribeService::class);

        $granularUrl = null;
        $granularText = null;
        $categoryUrl = '';
        $categoryText = '';

        switch ($this->commentableType) {
            case CommentableType::Game:
                $granularUrl = $unsubscribeService->generateGranularUrl(
                    $notifiable,
                    SubscriptionSubjectType::GameWall,
                    $this->activityId
                );
                $granularText = 'Unsubscribe from this game wall';

                $categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $notifiable,
                    UserPreference::EmailOn_AchievementComment
                );
                $categoryText = 'Unsubscribe from all wall comment emails';
                break;

            case CommentableType::Achievement:
                $granularUrl = $unsubscribeService->generateGranularUrl(
                    $notifiable,
                    SubscriptionSubjectType::Achievement,
                    $this->activityId
                );
                $granularText = 'Unsubscribe from this achievement';

                $categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $notifiable,
                    UserPreference::EmailOn_AchievementComment
                );
                $categoryText = 'Unsubscribe from all achievement comment emails';
                break;

            case CommentableType::User:
                $granularUrl = $unsubscribeService->generateGranularUrl(
                    $notifiable,
                    SubscriptionSubjectType::UserWall,
                    $this->activityId
                );
                $granularText = 'Unsubscribe from this user wall';

                $categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $notifiable,
                    UserPreference::EmailOn_UserWallComment
                );
                $categoryText = 'Unsubscribe from all user wall comment emails';
                break;

            case CommentableType::Forum:
                $granularUrl = $unsubscribeService->generateGranularUrl(
                    $notifiable,
                    SubscriptionSubjectType::ForumTopic,
                    $this->activityId
                );
                $granularText = 'Unsubscribe from this forum thread';

                $categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $notifiable,
                    UserPreference::EmailOn_ForumReply
                );
                $categoryText = 'Unsubscribe from all forum reply emails';
                break;

            case CommentableType::AchievementTicket:
                $categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $notifiable,
                    UserPreference::EmailOn_TicketActivity
                );
                $categoryText = 'Unsubscribe from all ticket activity emails';
                break;

            default:
                $categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $notifiable,
                    UserPreference::EmailOn_ActivityComment
                );
                $categoryText = 'Unsubscribe from all activity comment emails';
                break;
        }

        return [
            'granularUrl' => $granularUrl,
            'granularText' => $granularText,
            'categoryUrl' => $categoryUrl,
            'categoryText' => $categoryText,
        ];
    }
}
