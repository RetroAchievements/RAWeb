<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameTitleRegion: string
{
    case Africa = 'af';
    case Australia = 'au';
    case Brazil = 'br';
    case China = 'cn';
    case Europe = 'eu';
    case India = 'in';
    case Japan = 'jp';
    case LatinAmerica = 'latam';
    case MiddleEast = 'me';
    case NorthAmerica = 'na';
    case Russia = 'ru';
    case SoutheastAsia = 'sea';
    case SouthKorea = 'kr';
    case TaiwanHongKong = 'tw_hk';

    case Worldwide = 'worldwide';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Africa => 'Africa (AF)',
            self::Australia => 'Australia (AU)',
            self::Brazil => 'Brazil (BR)',
            self::China => 'China (CN)',
            self::Europe => 'Europe (EUR)',
            self::India => 'India (IN)',
            self::Japan => 'Japan (JP)',
            self::LatinAmerica => 'Latin America (not Brazil) (LATAM)',
            self::MiddleEast => 'Middle East (ME)',
            self::NorthAmerica => 'North America (NA)',
            self::Russia => 'Russia (RU)',
            self::SoutheastAsia => 'Southeast Asia (SEA)',
            self::SouthKorea => 'South Korea (KR)',
            self::TaiwanHongKong => 'Taiwan/Hong Kong (TW/HK)',

            self::Worldwide => 'Worldwide (WW)',
            self::Other => 'Other',
        };
    }
}
