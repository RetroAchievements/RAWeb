<?php

declare(strict_types=1);

namespace App\Community\Enums;

abstract class TicketAction
{
    public const ClosedMistaken = 'closed-mistaken';

    public const Resolved = 'resolved';

    public const Demoted = 'demoted';

    public const NotEnoughInfo = 'not-enough-info';

    public const WrongRom = 'wrong-rom';

    public const Network = 'network';

    public const UnableToReproduce = 'unable-to-reproduce';

    public const ClosedOther = 'closed-other';

    public const Request = 'request';

    public const Reopen = 'reopen';

    public static function cases(): array
    {
        return [
            self::ClosedMistaken,
            self::Resolved,
            self::Demoted,
            self::NotEnoughInfo,
            self::WrongRom,
            self::Network,
            self::UnableToReproduce,
            self::ClosedOther,
            self::Request,
            self::Reopen,
        ];
    }
}
