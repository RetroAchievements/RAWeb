<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameReleaseRegion: string
{
    case Asia = 'as';
    case Australia = 'au';
    case Brazil = 'br';
    case China = 'ch';
    case Europe = 'eu';
    case Japan = 'jp';
    case Korea = 'kr';
    case NewZealand = 'nz';
    case NorthAmerica = 'na';

    case Worldwide = 'worldwide';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Asia => 'Asia (AS)',
            self::Australia => 'Australia (AU)',
            self::Brazil => 'Brazil (BR)',
            self::China => 'China (CH)',
            self::Europe => 'Europe (EU)',
            self::Japan => 'Japan (JP)',
            self::Korea => 'Korea (KR)',
            self::NewZealand => 'New Zealand (NZ)',
            self::NorthAmerica => 'North America (NA)',

            self::Worldwide => 'Worldwide (WW)',
            self::Other => 'Other',
        };
    }
}
