<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum MessageThreadTemplateKind: string
{
    case AchievementIssue = 'achievement-issue';
    case ManualUnlock = 'manual-unlock';
    case Misclassification = 'misclassification';
    case UnwelcomeConcept = 'unwelcome-concept';
    case WritingError = 'writing-error';
}
