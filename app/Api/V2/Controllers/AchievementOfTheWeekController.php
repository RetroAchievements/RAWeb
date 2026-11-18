<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Api\V2\Data\AchievementOfTheWeekData;
use App\Api\V2\Data\AchievementOfTheWeekMetaData;
use App\Api\V2\Data\CurrentEventAchievementData;
use App\Api\V2\Data\SelfLinkData;
use App\Models\EventAchievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\OpenApiSpec\Attributes\WithDescription;

class AchievementOfTheWeekController
{
    #[WithDescription(
        AchievementOfTheWeekData::class,
        'Resolve the current Achievement of the Week',
        alsoDocument: ['404'],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $eventAchievement = EventAchievement::currentAchievementOfTheWeek()
            ->with(['achievement', 'sourceAchievement', 'event'])
            ->first();

        if (!$eventAchievement?->sourceAchievement || !$request->user()?->can('view', $eventAchievement)) {
            throw JsonApiException::error([
                'status' => '404',
                'title' => 'Not Found',
                'detail' => 'There is no active Achievement of the Week.',
            ]);
        }

        return response()->json(new AchievementOfTheWeekData(
            links: new SelfLinkData(self: $request->fullUrl()),
            meta: new AchievementOfTheWeekMetaData(
                eventAchievement: new CurrentEventAchievementData(
                    id: (string) $eventAchievement->id,
                    achievementId: (string) $eventAchievement->achievement_id,
                    sourceAchievementId: (string) $eventAchievement->source_achievement_id,
                    eventId: (string) $eventAchievement->event->id,
                    activeFrom: $eventAchievement->active_from->toISOString(),
                    activeUntil: $eventAchievement->active_until->toISOString(),
                    activeThrough: $eventAchievement->active_through->toISOString(),
                    decorator: $eventAchievement->decorator,
                ),
            ),
        ));
    }
}
