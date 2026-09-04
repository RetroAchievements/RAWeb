<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use App\Community\Enums\TicketType;
use App\Models\Emulator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketListFilterKind: string
{
    case Type = 'type';
    case PublishedStatus = 'publishedStatus';
    case Mode = 'mode';
    case DeveloperType = 'developerType';
    case Developer = 'developer';
    case Reporter = 'reporter';
    case Emulator = 'emulator';

    /**
     * These are listed in display order.
     *
     * @return string[]
     */
    public function values(?int $systemId = null): array
    {
        return match ($this) {
            self::Type => [
                '0',
                (string) TicketType::TriggeredAtWrongTime->toLegacyInteger(),
                (string) TicketType::DidNotTrigger->toLegacyInteger(),
            ],
            self::PublishedStatus => ['all', 'published', 'unpublished'],
            self::Mode => ['all', 'hardcore', 'softcore', 'unspecified'],
            self::DeveloperType => ['all', 'active', 'junior', 'inactive'],
            self::Developer, self::Reporter => ['all', 'self', 'others'],
            self::Emulator => ['all', ...self::emulatorNames($systemId), 'unknown'],
        };
    }

    public function noFilterValue(): int|string
    {
        return $this === self::Type ? 0 : 'all';
    }

    /**
     * @return array<int, string|\Illuminate\Validation\Rules\In>
     */
    public function validationRules(): array
    {
        return match ($this) {
            self::Type => ['sometimes', 'integer', Rule::in($this->values())],
            self::Emulator => ['sometimes', 'string'],
            default => ['sometimes', 'string', Rule::in($this->values())],
        };
    }

    /**
     * @return string[]
     */
    private static function emulatorNames(?int $systemId): array
    {
        $emulators = $systemId
            ? Emulator::forSystem($systemId)
            : Emulator::whereIn('id', DB::table('system_emulators')->distinct()->pluck('emulator_id')->all());

        return $emulators->orderBy('name')->pluck('name')->all();
    }
}
