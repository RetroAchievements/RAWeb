<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Enums\MessageThreadTemplateKind;
use App\Community\Enums\ModerationReportableType;
use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('MessageThreadCreatePageProps')]
class MessageThreadCreatePagePropsData extends Data
{
    public function __construct(
        public ?UserData $toUser,
        public ?string $message,
        public ?string $subject,
        public ?MessageThreadTemplateKind $templateKind,
        public ?string $senderUserAvatarUrl,
        public string $senderUserDisplayName,
        public ?ModerationReportableType $reportableType = null,
        public ?int $reportableId = null,
    ) {
    }
}
