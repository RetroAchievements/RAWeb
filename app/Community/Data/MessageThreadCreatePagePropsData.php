<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Enums\MessageThreadTemplateKind;
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
        public UserData $senderUser,
    ) {
    }
}
