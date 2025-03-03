<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\AwardType;
use App\Models\Achievement;
use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\EventAward;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\AttachPlayerGameAction;
use App\Platform\Actions\DetachGamesFromGameSetAction;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncEvents extends Command
{
    protected $signature = 'ra:sync:events {gameId?}';
    protected $description = 'Sync events from event games';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        if (Schema::hasColumn('event_awards', 'achievements_required')) {
            Schema::table('event_awards', function (Blueprint $table) {
                $table->renameColumn('achievements_required', 'points_required');
            });
        }

        // special conversions
        //PlayerBadge::where('user_id', User::where('User', 'jplima')->first()->id)->where('AwardData', 1018)->update(['AwardDataExtra' => 1]); // eliminates softcore badge for Devember 2019

        //PlayerBadge::whereIn('user_id', User::whereIn('User', ['Pebete', 'AuburnRDM', 'TheJediSonic', 'Fridge', 'DanB'])->pluck('ID'))
        //    ->where('AwardData', 7970)->update(['AwardDataExtra' => 1]); // eliminates softcore badge for Devember 2022

        $gameConversions = [
            // ===== 2017 =====

            //1963 => new ConvertAsIs('solar-jetman'),
            //11128 => new ConvertAsIs('pumpkin-king-2017', '10/13/2017', '10/31/2017'),
            11597 => new ConvertAsIs('christmas-2017', '12/1/2017', '1/7/2018'),

            // ===== 2018 =====

            13755 => new ConvertCollapse('aotw-2018', '1/1/2018', '12/31/2018'),
            13279 => new ConvertToTracked('aotw-2018-halloween', [
                16187 => ['10/6/2018', '10/12/2018'],
                15585 => ['10/13/2018', '10/19/2018'],
                15025 => ['10/20/2018', '10/26/2018'],
                3230 => ['10/27/2018', '11/2/2018'],
            ]),
            13447 => new ConvertToTracked('aotw-2018-christmas', [
                601 => ['11/30/2018', '12/6/2018'],
                2530 => ['12/7/2018', '12/13/2018'],
                31510 => ['12/14/2018', '12/20/2018'],
                31278 => ['12/21/2018', '12/27/2018'],
            ]),
            13448 => new ConvertToTiered('devember-2018', [1 => '1 set fixed', 2 => '2 sets fixed'], [
                68551 => ['Hotscrock'],
                68552 => ['BenGhazi'],
                68553 => ['Keltron3030', 'televandalist'],
                68554 => ['SporyTike', 'kdecks', 'Salsa', 'JAM'],
                68555 => ['Thoreau', 'Blazekickn', 'theztret00', 'Grenade44', 'DrPixel', 'ColonD', 'Tutumos'],
                68556 => ['Zaphnath', 'Rimsala', 'SirVG', 'Jamiras', 'deividokop', 'MGNS8M', 'ikki5'],
            ]),

            // ===== 2019 =====

            1046 => new ConvertAotWTiered('aotw-2019', '1/4/2019', [20 => 1023, 30 => 1026, 40 => 1046], [
                7091, 7277, 3975, 67795, 20262, 32418, 37147, 22135, 70572, 56991, 5359, 60531,
                19375, 49583, 2288, 1879, 4572, 70351, 61774, 70295, 28572, 73619, 20140, 18654,
                5317, 23921, 53755, 34218, 51324, 55867, 50826, 53144, 11710, 17421, 73124, 26313,
                80126, 3817, 56042, 8275, 3077, 63183, 32640, 1241, 30129, 8508, 41530, 52819,
                21275, 9432, 76751, 91736,
            ]),
            1119 => new ConvertToCollapsedTiered('aotw-2019-top-players', 'Achievement of the Week 2019 Top Players',
                [1078 => '47 points', 1114 => '60.5 points', 1119 => '61 points'],
                [93204, 93210, 93216],
            ),
            14315 => new ConvertToTracked('aotw-2019-spring', [
                19375 => ['3/29/2019', '4/4/2019'],
                49583 => ['4/5/2019', '4/11/2019'],
                2288 => ['4/12/2019', '4/18/2019'],
                1879 => ['4/19/2019', '4/25/2019'],
            ]),
            14314 => new ConvertToTracked('aotw-2019-spooky', [
                8275 => ['10/4/2019', '10/10/2019'],
                3077 => ['10/11/2019', '10/17/2019'],
                63183 => ['10/18/2019', '10/24/2019'],
                32640 => ['10/25/2019', '10/31/2019'],
            ]),
            14400 => new ConvertCollapse('community-rescore', '7/14/2019', '7/28/2019'),
            14404 => new ConvertCollapse('retro-cleanup-2019', '3/1/2019', '3/31/2019'),
            1018 => new ConvertToSoftcoreTiered('devember-2019', 'Any points', '320 points', 90625),

            // ===== 2020 =====

            9569 => new ConvertAotWTiered('aotw-2020', '1/5/2020', [16 => 9550, 32 => 5929, 48 => 7725, 64 => 9569], [
                30631, 49307, 92276, 34853, 71436, 81977, 84142, 98264, 69285, 21833, 12613, 67132,
                22128, 77147, 23441, 5854, 71060, 70473, 106331, 3181, 34529, 38502, 108013, 80055,
                12950, 33225, 7569, 93487, 38438, 75779, 66435, 48348, 2040, 45399, 70566, 80104,
                62503, 6050, 105837, 39623, 14643, 128968, 100650, 7287, 12364, 46479, 67139, 50940,
                114492, 7311, 78373, 85393,
            ], [2388, 10660, 11846, 49687, [53694, 53697, 53700, 53685], 60521, 9793, 67629, 63579, 48860, 99742, 55509],
                extraDay: true),
            4721 => new ConvertToTracked('aotw-2020-camping', [
                48348 => ['8/9/2020', '8/16/2020'],
                2040 => ['8/16/2020', '8/23/2020'],
                45399 => ['8/23/2020', '8/30/2020'],
                70566 => ['8/30/2020', '9/6/2020'],
            ]),
            9834 => new ConvertAsIs('spring-cleaning-2020'),
            15905 => new ConvertToTracked('aotw-2020-christmas', [
                50940 => ['11/29/2020', '12/5/2020'],
                114492 => ['12/6/2020', '12/12/2020'],
                7311 => ['12/13/2020', '12/19/2020'],
                78373 => ['12/20/2020', '12/26/2020'],
                85393 => ['12/27/2020', '1/2/2021'],
            ]),
            15936 => new ConvertToTracked('aotm-2020', [
                2388 => ['1/5/2020', '2/1/2020'],
                10660 => ['2/2/2020', '3/7/2020'],
                11846 => ['3/8/2020', '4/4/2020'],
                49687 => ['4/5/2020', '5/2/2020'],
                53685 => ['5/3/2020', '6/6/2020', [53694, 53697, 53700, 53685]],
                60521 => ['6/7/2020', '7/4/2020'],
                9793 => ['7/5/2020', '8/1/2020'],
                67629 => ['8/2/2020', '9/5/2020'],
                63579 => ['9/6/2020', '10/3/2020'],
                48860 => ['10/4/2020', '11/7/2020'],
                99742 => ['11/8/2020', '12/5/2020'],
                55509 => ['12/6/2020', '1/2/2021'],
            ]),
            3046 => new ConvertAsIs('jr-dev-graduate'),
            8275 => new ConvertAsIs('unwanted-bronze', '07/15/2020'),
            3530 => new ConvertAsIs('unwanted-silver', '07/15/2020'),
            3904 => new ConvertAsIs('unwanted-gold', '07/15/2020'),
            15945 => new ConvertAsIs('unwanted-platinum', '07/15/2020', noWinners: true),
            5982 => new ConvertAsIs('challenge-league-2'),
            //17076 => new ConvertAsIs('communiplay'),
            17310 => new ConvertAsIs('devember-2020', '11/15/2020', '12/13/2020'),
            2785 => new ConvertToMergedTracked('tba-2020', 'The Big Achievement 2020',
                [5 => 6005, 15 => 4076, 30 => 6872, 40 => 2785],
                [
                    1004 => ['1/1/2020', '1/14/2020'],
                    5794 => ['1/15/2020', '1/28/2020'],
                    5561 => ['1/29/2020', '2/11/2020'],
                    54856 => ['2/12/2020', '2/25/2020'],
                    70263 => ['2/26/2020', '3/24/2020'],
                    78686 => ['3/25/2020', '4/7/2020'],
                    37529 => ['4/8/2020', '4/23/2020'],
                    36104 => ['4/24/2020', '5/8/2020'],
                    108577 => ['4/9/2020', '5/23/2020', [63880, 108577]],
                    18405 => ['5/23/2020', '6/5/2020'],
                    10920 => ['6/6/2020', '6/18/2020'],
                    87607 => ['6/19/2020', '7/2/2020'],
                    3633 => ['7/3/2020', '7/16/2020'],
                    15104 => ['7/17/2020', '7/31/2020', [117022, 15104]],
                    5722 => ['8/1/2020', '8/16/2020'],
                    54539 => ['8/17/2020', '8/30/2020'],
                    41791 => ['8/31/2020', '9/12/2020'],
                    98035 => ['9/13/2020', '9/26/2020'],
                    277 => ['9/27/2020', '10/10/2020'],
                    36680 => ['10/11/2020', '10/24/2020'],
                    89846 => ['10/25/2020', '11/7/2020'],
                    26186 => ['11/8/2020', '11/21/2020'],
                    56763 => ['11/22/2020', '12/6/2020'],
                    114454 => ['12/7/2020', '12/20/2020'],
                    83160 => ['12/21/2020', '1/3/2021'],
                ],
                [
                    21488 => ['1/1/2020', '1/31/2020'],
                    'Leapfrog' => ['2/1/2020', '2/28/2020'],
                    'March Mastery' => ['3/1/2020', '3/31/2020'],
                    'TBA x VGM' => ['4/1/2020', '4/30/2020'],
                    106400 => ['5/1/2020', '5/31/2020'],
                    86433 => ['6/1/2020', '6/30/2020'],
                    33775 => ['7/1/2020', '7/31/2020'],
                    81438 => ['8/1/2020', '8/31/2020'],
                    49276 => ['9/1/2020', '9/30/2020', [56709, 49276]],
                    'Spooky Bingo' => ['10/1/2020', '10/31/2020'],
                    'Doomsday Challenge' => ['11/1/2020', '11/30/2020'],
                    'First to Master' => ['12/1/2020', '12/31/2020'],
                ]
            ),

            // ===== 2021 =====

            3487 => new ConvertAsIs('challenge-league-3', '2/1/2021', '1/31/2022'),
            15940 => new ConvertCollapse('devquest-001'),
            15957 => new ConvertCollapse('devquest-002'),
            15953 => new ConvertCollapse('devquest-003'),
            15947 => new ConvertAsIs('devquest-004'),
            15950 => new ConvertAsIs('playtester'),
            15951 => new ConvertAsIs('great-jacko', '4/2/2021', '5/1/2021'),
            15952 => new ConvertCollapse('devquest-005'),
            15907 => new ConvertCollapse('devquest-006'),
            17758 => new ConvertCollapse('devquest-007'),
            15918 => new ConvertCollapse('devquest-008'),
            1246 => new ConvertAsIs('unwanted2-bronze'),
            1266 => new ConvertAsIs('unwanted2-silver'),
            1273 => new ConvertAsIs('unwanted2-gold', noWinners: true),
            15937 => new ConvertCollapse('devquest-009'),
            15939 => new ConvertCollapse('devquest-010'),
            15949 => new ConvertToCollapsedTiered('retroolympics-2021', 'RetroOlympics 2021',
                [15946 => 'Bronze', 15948 => 'Silver', 15949 => 'Gold'],
                [179688, 179694, 179700],
            ),
            1280 => new ConvertCollapse('devquest-011'),
            910 => new ConvertToTiered('devember-2021', [1 => '50 points', 2 => '150 points'], [
                187531 => ['SlashTangent'],
                187532 => ['Snow'],
                187536 => ['televandalist'],
                187533 => ['Etron'],
                187534 => ['Bartis1989'],
                187535 => 'to_hardcore',
            ]),

            // ===== 2022 =====

            672 => new ConvertCollapse('devquest-012'),
            795 => new ConvertCollapse('devquest-013'),
            862 => new ConvertCollapse('devquest-014'),
            809 => new ConvertAsIs('devquest-015'),
            8000 => new ConvertToTiered('distractions-1', [1 => '30 points', 2 => '60 points'], [
                205862 => 'hardcore_only',
                205863 => 'to_hardcore',
            ]),
            18999 => new ConvertCollapse('quality-quest'),
            2962 => new ConvertCollapse('devquest-016'),
            19704 => new ConvertCollapse('rawr-2022'),
            18858 => new ConvertToTiered('distractions-2', [1 => '30 points', 2 => '60 points'], [
                233732 => 'hardcore_only',
                233733 => 'to_hardcore',
            ]),
            8069 => new ConvertAotWTiered('aotw-2021', '1/3/2021', [16 => 14872, 32 => 17306, 48 => 17426, 64 => 8069], [
                58360, 16264, 137451, 115451, 4738, 117123, 4673, 114219, 78685, 136014, 13126, 133517,
                50441, 31586, 48543, 73980, 16869, 47172, 13566, 70089, 129974, 125937, 156172, 26519,
                155612, 156253, 8306, 131820, 102129, 109418, 100414, 140885, 124425, 15569, 34402, 150781,
                80943, 101418, 168388, 48648, 2811, 80125, 71178, 98638, 13072, 140047, 19595, 97397,
                157177, 1344, 82308, 176857
            ], [[125205, 5959], 48511, 5825, 53312, 14905, 146742, 67230, 5561, 88350, 91715, 125572, 136669],
                extraDay: true),
            3855 => new ConvertCollapse('aotw-2021-halloween', '10/3/2021', '11/6/2021'),
            3856 => new ConvertCollapse('aotw-2021-festive', '12/5/2021', '1/1/2022'),
            // 6189 => new ConvertToTiered('lotm', [1 => '100 points', 2 => '200 points'], [
            //     238014 => 'to_hardcore',
            //     238015 => 'hardcore_only',
            // ]),
            15942 => new ConvertCollapse('devquest-017'),
            7970 => new ConvertAsIs('devember-2022'),
            8032 => new ConvertCollapse('leapfrog'),
            7938 => new ConvertCollapse('leapfrog-2'),
            7980 => new ConvertCollapse('leapfrog-ex'),
            9858 => new ConvertToTiered('leapfrog-4', [1 => 'Survived 14 days', 2 => 'Survived 29 days'], [
                111931 => ['Xymjak', 'Klarth18'], // 90
                111932 => ['Searo'], // 75
                111933 => ['Haruda', 'Blazekickn', 'televandalist'], // 60
                111934 => ['Shmelyoff', 'NickGoat1990', 'Nevermond12', 'jltn', 'BendyHuman'], // 45
                111935 => ['RetroRobb', 'SteevL', 'DrPixel', 'jos', 'Gamechamp', 'TheRecognitionScene',
                    'EverElsewhere', 'Boldewin'], // 29
                111936 => ['timmytenfingers', 'Snow', 'matheus2653', 'BenGhazi', 'KickMeElmo', 'Cactuarin247', 'LootusMaximus',
                    'RABarcade', 'ObsoleteGamer2004', 'Tvols1480', 'Hotscrock'], // 14
            ]),
            3903 => new ConvertCollapse('leapfrog-5'),
            3906 => new ConvertAsIs('challenge-league'),
            7998 => new ConvertAsIs('rpm'),
            22094 => new ConvertAotWTiered('aotw-2022', '1/3/2022', [16 => 22091, 32 => 22092, 48 => 22093, 64 => 22094], [
                82634, 112016, 26996, 103469, 47314, 3524, 104634, 33246, 3369, 1904, 70239, 66977,
                27089, 7071, 179974, 169496, 177842, 83357, 18523, 93571, 56752, 43874, 189217, 25036,
                217350, 48117, 187270, 126929, 51865, 3078, 48615, 24684, 142853, 92424, 229835, 52861,
                150395, 140379, 51502, 235444, 165062, 191610, 240391, 1, 1801, 113871, 49123, 146664,
                234608, 261040, 225742, 28312
            ], [81716, 1004, 49219, 7738, 162456, [19377, 19381, 19379], 39537, 99991, 119231, 228082, 228878, 173962]),
            22095 => new ConvertCollapse('ps2-launch-bronze'),
            22096 => new ConvertCollapse('ps2-launch-silver'),
            22097 => new ConvertAsIs('ps2-launch-gold'),
            3920 => new ConvertAsIs('cl2022-completion'),
            3961 => new ConvertAsIs('cl2022-mastery'),
            7937 => new ConvertAsIs('cl2022-bonus'),

            // ===== backfill (done in 2022) =====

            //15943 => new ConvertAsIs('aotw-2014'),
            15943 => new ConvertToTracked('aotw-2014', [
                1801 => ['2/10/2014', '2/17/2014'], // t=506
                3542 => ['2/18/2014', '2/24/2014'], // t=547
                4347 => ['2/25/2014', '3/3/2014'], // t=614
                5166 => ['3/4/2014', '3/10/2014'], // t=656
                1955 => ['3/11/2014', '3/17/2014'], // t=688
                5066 => ['3/18/2014', '3/24/2014'], // t=725
                2241 => ['3/25/2014', '3/30/2014'], // t=748
                6366 => ['3/31/2014', '4/6/2014'], // t=780
                5247 => ['4/7/2014', '4/14/2014'], // t=810
                22 => ['4/15/2014', '4/21/2014'], // t=832
                3320 => ['4/22/2014', '4/28/2014'], // t=846
                7508 => ['4/29/2014', '5/5/2014'], // t=871
                6961 => ['5/6/2014', '5/12/2014'], // t=885
                4643 => ['5/12/2014', '5/18/2014'], // t=913
                3131 => ['5/19/2014', '5/26/2014'], // t=930
                6082 => ['5/27/2014', '6/8/2014'], // t=939
                1339 => ['6/9/2014', '6/15/2014'], // t=950
                262 => ['6/16/2014', '6/23/2014'], // t=960
                4927 => ['6/24/2014', '6/30/2014'], // t=986
                6694 => ['7/1/2014', '7/7/2014'], // t=1000
                1752 => ['7/8/2014', '7/13/2014'], // t=1012
                969 => ['7/14/2014', '7/21/2014'], // t=1022
                9845 => ['7/22/2014', '7/28/2014'], // t=1034
                898 => ['7/29/2014', '8/4/2014'], // t=1046
                7278 => ['8/5/2014', '8/12/2014'], // t=1054
                51772 => ['8/13/2014', '8/17/2014'], // t=1064
                3057 => ['8/18/2014', '8/24/2014'], // t=1079
                9980 => ['8/25/2014', '8/31/2014'], // t=1100
                281 => ['9/1/2014', '9/7/2014'], // t=1111
                9627 => ['9/8/2014', '9/14/2014'], // t=1128
                311 => ['9/15/2014', '9/21/2014'], // t=1155
                11595 => ['9/22/2014', '9/28/2014'], // t=1171
                13577 => ['9/29/2014', '10/5/2014'], // t=1186
                13608 => ['10/6/2014', '10/12/2014'], // t=1206
                6224 => ['10/13/2014', '10/19/2014'], // t=1226
                8368 => ['10/20/2014', '10/26/2014'], // t=1241
                16198 => ['10/27/2014', '11/10/2014'], // t=1266
                8584 => ['11/11/2014', '11/17/2014'], // t=1299
                12205 => ['11/18/2014', '11/25/2014'], // t=1306
                6393 => ['11/26/2014', '12/1/2014'], // t=1313
                11991 => ['12/2/2014', '12/8/2014'], // t=1316
                7198 => ['12/9/2014', '12/15/2014'], // t=1323
                7762 => ['12/16/2014', '12/23/2014'], // t=1327
                1893 => ['4/7/2014', '4/21/2014'], // t=810 two week challenge
                4708 => ['4/29/2014', '5/11/2014'], // t=871 two week challenge
                5880 => ['5/27/2014', '6/23/2014'], // t=939 month challenge
                8644 => ['6/24/2014', '7/21/2014'], // t=986 month challenge
                6801 => ['7/22/2014', '8/19/2014'], // t=1034 month challenge
                11117 => ['8/18/2014', '9/15/2014'], // t=1079 month challenge
                7628 => ['9/15/2014', '10/13/2014'], // t=1155 month challenge
                91819 => ['10/13/2014', '11/10/2014'], // t=1226 month challenge (master)
                2759 => ['11/11/2014', '12/8/2014'], // t=1299 month challenge
            ]),
            15944 => new ConvertAsIs('aotw-2015'),
            // 15944 => new ConvertToTracked('aotw-2015', [
            //     2027 => ['1/6/2015', '1/12/2015'], // t=1370
            //     3227 => ['1/12/2015', '1/19/2015'], // t=1385
            //     5150 => ['1/20/2015', '1/26/2015'], // t=1394
            //     14565 => ['1/26/2015', '2/2/2015'], // t=1410
            //     18930 => ['2/3/2015', '2/10/2015'], // t=1420
            //     12618 => ['2/11/2015', '2/17/2015'], // t=1442
            //     8658 => ['2/17/2015', '2/26/2015'], // t=1457
            //     21285 => ['2/27/2015', '3/4/2015'], // t=1485
            //     8266 => ['3/5/2015', '3/11/2015'], // t=1499
            //     14223 => ['3/12/2015', '3/18/2015'], // t=1512
            //     19959 => ['3/19/2015', '3/22/2015'], // ??
            //     21112 => ['3/23/2015', '3/30/2015'], // t=1523 [55]
            //     18302 => ['3/31/2015', '4/13/2015'], // ??
            //     12445 => ['4/14/2015', '4/20/2015'], // t=1549 [57]
            //     10578 => ['4/21/2015', '4/26/2015'], // t=1552 [58]
            //     8053 => ['4/27/2015', '5/3/2015'], // 59 => MMPR
            //     2000 => ['5/4/2015', '5/10/2015'], // 60 => Quackshot
            //     5942 => ['5/11/2015', '5/17/2015'], // 61 => Kirby's Dreamland 2
            //     8316 => ['5/18/2015', '5/26/2015'], // t=1605 [62]
            //     5481 => ['5/27/2015', '6/9/2015'], // t=1634 [63]
            //     3893 => ['6/10/2015', '6/16/2015'], // t=1740 [64]
            //     23122 => ['6/17/2015', '6/22/2015'], // t=1760 [65]
            //     10863 => ['6/23/2015', '6/29/2015'], // 66 => Snoopy
            //     19026 => ['6/30/2015', '7/5/2015'], // t=1860 [67]
            //     1141 => ['7/6/2015', '7/12/2015'], // 68 => Metal Slug Advance
            //     4656 => ['7/13/2015', '7/20/2015'], // t=1939 [69]
            //     25609 => ['7/21/2015', '7/27/2015'], // t=2017 [70]
            //     22026 => ['7/28/2015', '8/2/2015'], // t=2082 [71]
            //     23159 => ['8/3/2015', '8/10/2015'], // t=2173 [72]
            //     4461 => ['8/11/2015', '8/17/2015'], // t=2173 [73]
            //     19855 => ['8/18/2015', '8/24/2015'], // 74 = Ducktales2
            //     23061 => ['8/25/2015', '8/31/2015'], // t=2234 [75]
            //     22936 => ['9/1/2015', '9/7/2015'], // t=2265 [76]
            //     7923 => ['9/8/2015', '9/14/2015'],
            //     26148 => ['9/22/2015', '9/28/2015'],
            //     17760 => ['1/6/2015', '2/2/2015'], // t=1370 monthly
            //     10052 => ['2/3/2015', '3/2/2015'], // t=1420 monthly
            //     10700 => ['3/3/2015', '3/30/2015'], // t=1499 monthly
            //     14627 => ['4/14/2015', '5/11/2015'], // t=1548 monthly
            //     21357 => ['5/18/2015', '6/15/2015'], // t=1605 monthly
            //     17037 => ['6/17/2015', '7/13/2015'], // t=1760 monthly
            //     7824 => ['7/13/2015', '8/10/2015'], // t=1939 monthly
            //     21541 => ['8/11/2015', '9/7/2015'], // t=2173 monthly
            // ]),
            3892 => new ConvertAsIs('aotw-2016'),
            // 3892 => new ConvertToTracked('aotw-2016', [
            //     29960 => ['11/10/2015', '11/16/2015'], // SMW
            //     4717 => ['11/17/2015', '11/23/2015'], // t=2495 [2]
            //     27626 => ['11/24/2015', '11/30/2015'], // t=2529 [3]
            //     670 => ['12/1/2015', '12/7/2015'], // t=2544 [4]
            //     18302 => ['1/11/2016', '1/17/2016'], // t=2673 [5]
            //     10521 => ['2/14/2016', '2/20/2016'], // t=2768 [6]
            //     15979 => ['6/1/2016', '6/7/2016'], // t=3197 [7]

            //     26608 => ['11/17/2015', '11/30/2015'], // t=2495
            //     27960 => ['11/17/2015', '11/30/2015'], // t=2495
            //     11520 => ['12/1/2015', '12/31/2015'], // t=2544
            //     21763 => ['12/1/2015', '12/31/2015'], // t=2544
            //     19806 => ['1/11/2016', '1/31/2016'], // t=2673
            //     11038 => ['1/11/2016', '1/31/2016'], // t=2673
            //     10529 => ['2/14/2016', '2/28/2016'], // t=2768
            //     5957 => ['2/14/2016', '2/28/2016'], // t=2768
            //     5801 => ['6/1/2016', '6/30/2016'], // t=3197
            //     10229 => ['6/1/2016', '6/30/2016'], // t=3197
            // ]),
            8043 => new ConvertAsIs('aotw-2017'),

            // ===== 2023 =====

            22561 => new ConvertCollapse('devquest-001-2'),
            22562 => new ConvertCollapse('devquest-002-2'),
            22563 => new ConvertCollapse('devquest-013-2'),
            22564 => new ConvertCollapse('devquest-018'),
            22565 => new ConvertCollapse('devquest-019'),
            22566 => new ConvertCollapse('devquest-016-2'),
            3911 => new ConvertToSoftcoreTiered('distractions-3', '30 points', '90 points'),
            7950 => new ConvertAsIs('rawr-2023'),
            7939 => new ConvertAsIs('retroolympics-2022-bronze'),
            7984 => new ConvertAsIs('retroolympics-2022-silver'),
            8014 => new ConvertAsIs('retroolympics-2022-gold'),
            25672 => new ConvertAsIs('devquest-020'),
            25673 => new ConvertAsIs('devquest-020-subgenre', noWinners: true),
            25674 => new ConvertCollapse('devquest-001-3'),
            25675 => new ConvertCollapse('devquest-002-3'),
            25676 => new ConvertCollapse('devquest-013-3'),
            25677 => new ConvertCollapse('devquest-021'),
            25678 => new ConvertCollapse('devquest-022'),
            8028 => new ConvertAsIs('console-wars-completion', '2023-03-11', '2023-08-18'),
            8030 => new ConvertAsIs('console-wars-mastery', '2023-03-11', '2023-08-18'),
            8033 => new ConvertAsIs('console-wars-bonus', '2023-03-11', '2023-08-18'),
            7996 => new ConvertAsIs('ffv-fjf-superboss', '2023-06-15', '2023-09-17'),
            7995 => new ConvertAsIs('ffv-fjf-exdeath', '2023-06-15', '2023-09-17'),
            8013 => new ConvertAsIs('halloween-2023-bronze', '2023-10-16', '2023-10-31'),
            7972 => new ConvertAsIs('halloween-2023-silver', '2023-10-16', '2023-10-31'),
            3954 => new ConvertAsIs('halloween-2023-gold', '2023-10-16', '2023-10-31'),
            27430 => new ConvertToCollapsedTiered('cream-of-the-crop', 'Cream of the Crop',
                [27428 => '3rd-7th in your house', 27429 => '2nd in your house', 27430 => '1st in your house'],
                [374063, 374069, 374075],
            ),
        ];

        $id = $this->argument('gameId');
        if ($id) {
            if (!array_key_exists($id, $gameConversions)) {
                $this->error("No conversion defined for game $id");

                return;
            }

            $gameConversions = [
                $id => $gameConversions[$id],
            ];

            // when processing a single game, run all the jobs synchronously so the validation
            // can check the final state.
            config(['queue.default' => 'sync']);
        }

        $gameCount = count($gameConversions);

        $this->info("\nUpserting {$gameCount} events derived from event games.");
        $progressBar = $this->output->createProgressBar($gameCount);

        foreach ($gameConversions as $gameId => $conversion) {
            if ($id) {
                $before = $conversion->captureBefore($gameId);
                DB::beginTransaction();
            }

            try {
                $conversion->convert($this, $gameId);

                if ($id) {
                    if ($conversion->validate($this, $gameId, $before)) {
                        DB::commit();
                    } else {
                        //DB::commit();
                        DB::rollBack();
                    }
                }
            } catch (Exception $e) {
                if ($id) {
                    DB::rollBack();
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->info("\nDone.");
    }
}

class ConvertGame
{
    protected string $slug;
    protected ?string $activeFrom = null;
    protected ?string $activeThrough = null;
    protected bool $noWinners = false;

    public function convert(Command $command, int $gameId): void
    {
        $game = Game::find($gameId);
        if (!$game) {
            $command->error("Game $gameID not found");

            return;
        }

        $event = $game->event;
        if (!$event) {
            $event = Event::create([
                'legacy_game_id' => $game->ID,
                'slug' => $this->slug,
                'image_asset_path' => $game->ImageIcon,
            ]);
        }

        if ($this->activeFrom && $this->activeThrough) {
            $event->active_from = Carbon::parse($this->activeFrom);
            $event->active_through = Carbon::parse($this->activeThrough);
            $event->save();
        }

        $this->convertSiteAwards($event);

        $this->process($command, $event);
    }

    protected function process(Command $command, Event $event): void
    {
    }

    protected function convertSiteAwards(Event $event): void
    {
        // only convert hardcore badges
        $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 1)
            ->update([
                'AwardType' => AwardType::Event,
                'AwardData' => $event->id,
                'AwardDataExtra' => 0,
            ]);

        // delete softcore badges
        PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 0)
            ->delete();
    }

    public function captureBefore(int $gameId): array
    {
        $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $gameId)
            ->orderBy('AwardDataExtra'); // force softcore awards first so they overwritten if the user also has a hardcore award

        $before = [];
        foreach ($badges->get() as $badge) {
            $before[$badge->user_id] = [
                'AwardDate' => $badge->AwardDate,
                'AwardDataExtra' => ($badge->AwardDataExtra === 1) ? 0 : -1,
            ];
        }

        return $before;
    }

    public function validate(Command $command, int $gameId, array $before): bool
    {
        $result = true;
        if (empty($before) && !$this->noWinners) {
            $command->error("No badges expected. Previously converted?");
            return false;
        }

        $event = Event::where('legacy_game_id', $gameId)->firstOrFail();
        $badges = PlayerBadge::where('AwardType', AwardType::Event)
            ->where('AwardData', $event->id);

        $after = [];
        foreach ($badges->get() as $badge) {
            $after[$badge->user_id] = [
                'AwardDate' => $badge->AwardDate,
                'AwardDataExtra' => $badge->AwardDataExtra,
            ];
        }

        $converted = 0;
        $deleted = 0;
        foreach ($before as $userId => $badge) {
            if (!array_key_exists($userId, $after)) {
                if ($badge['AwardDataExtra'] !== -1) {
                    $user = User::find($userId);
                    $command->error("Badge for user $userId ({$user->User}) lost in conversion.");
                    $result = false;
                } else {
                    $deleted++;
                }
            } else {
                $badgeAfter = $after[$userId];
                unset($after[$userId]);

                if ($badge['AwardDataExtra'] != $badgeAfter['AwardDataExtra']) {
                    $user = User::find($userId);
                    if ($badge['AwardDataExtra'] === -1) {
                        $command->error("Badge for user $userId ({$user->User}) was not deleted.");
                    } else {
                        $command->error("Badge for user $userId ({$user->User}) does not have expected tier_index {$badge['AwardDataExtra']}. Found {$badgeAfter['AwardDataExtra']}.");
                    }
                    $result = false;
                } elseif ($badge['AwardDate'] != $badgeAfter['AwardDate']) {
                    $user = User::find($userId);
                    $command->error("Badge for user $userId ({$user->User}) award date changed from " . $badge['AwardDate']->format("Y-m-d") . " to " . $badgeAfter['AwardDate']->format("Y-m-d"));
                    $result = false;
                } else {
                    $converted++;
                }
            }
        }

        foreach ($after as $userId => $badge) {
            $user = User::find($userId);
            $command->error("Badge for user $userId ({$user->User}) unexpected. Found tier {$badge['AwardDataExtra']}.");
            $result = false;
        }

        $command->info("Converted $converted badges." . ($deleted ? " Deleted $deleted badges." : ""));
        return $result;
    }

    protected function setAchievementCount(Event $event, int $count): void
    {
        // find the specified number of published achievements, and demote the rest
        $publishedCount = 0;
        $unofficialCount = 0;
        for ($index = 0; $index < $event->legacyGame->achievements->count(); $index++) {
            $achievement = $event->legacyGame->achievements->skip($index)->first();
            if ($achievement->Flags === AchievementFlag::OfficialCore->value) {
                if ($publishedCount === $count) {
                    $achievement->Flags = AchievementFlag::Unofficial->value;
                    $achievement->save();
                } else {
                    $publishedCount++;
                    $achievement->DisplayOrder = $publishedCount;
                    $achievement->Points = 1;
                    $achievement->save();
                }
            } else {
                $unofficialCount++;
            }
        }

        // didn't find enough published achievements, look for unpublished achievements to promote
        if ($publishedCount < $count && $unofficialCount > 0) {
            for ($index = 0; $index < $event->legacyGame->achievements->count(); $index++) {
                $achievement = $event->legacyGame->achievements->skip($index)->first();
                if ($achievement->Flags === AchievementFlag::Unofficial->value) {
                    $publishedCount++;

                    $achievement->DisplayOrder = $publishedCount;
                    $achievement->Points = 1;
                    $achievement->Flags = AchievementFlag::OfficialCore->value;
                    $achievement->save();

                    if ($publishedCount === $count) {
                        break;
                    }
                }
            }
        }

        while ($publishedCount < $count) {
            $publishedCount++;
            $achievement = Achievement::create([
                'Title' => 'Title',
                'Description' => 'Description',
                'Points' => 1,
                'MemAddr' => '0=1',
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $event->legacyGame->id,
                'user_id' => EventAchievement::RAEVENTS_USER_ID,
                'BadgeName' => '00000',
                'DisplayOrder' => $publishedCount,
            ]);
        }

        if ($event->legacyGame->achievements_published != $publishedCount) {
            $event->legacyGame->achievements_published = $publishedCount;
            $event->legacyGame->points_total = $publishedCount;
            $event->legacyGame->save();

            $event->legacyGame->refresh();

            // Force unlock count to 0 to prevent unintentionally upgrading badges as we migrate
            // achievements. It will get recalculated after the UpdateGameMetricsJob completes.
            PlayerGame::where('game_id', $event->legacyGame->id)
                ->update([
                    'achievements_unlocked_hardcore' => 0,
                    'updated_at' => DB::raw('updated_at'),
                ]);
        }
    }

    protected function createEventAchievement(Command $command, Achievement $achievement, ?int $sourceAchievementId = null,
        ?Carbon $activeFrom = null, ?Carbon $activeThrough = null): EventAchievement
    {
        if ($sourceAchievementId && !Achievement::exists($sourceAchievementId)) {
            $command->error("Could not find source achievement: $sourceAchievementId");

            return null;
        }

        // initialize the unlocks before creating the event achievements to prevent metrics cascading.
        // metrics will be forcibly recalculated after conversion completes.
        if ($sourceAchievementId) {
            // update unlock timestamps on the event achievements to match the source unlock
            $winners = PlayerAchievement::where('achievement_id', $sourceAchievementId)
                ->whereNotNull('unlocked_hardcore_at')
                ->join('UserAccounts', 'UserAccounts.ID', '=', 'player_achievements.user_id')
                ->select([
                    'player_achievements.user_id',
                    'player_achievements.unlocked_hardcore_at',
                    'UserAccounts.Untracked',
                    'UserAccounts.unranked_at',
                ]);

            if ($activeFrom) {
                $winners->where('unlocked_hardcore_at', '>=', $activeFrom);
            }
            if ($activeThrough) {
                $winners->where('unlocked_hardcore_at', '<', $activeThrough);
            }

            foreach ($winners->get() as $winner) {
                if ($winner->Untracked || $winner->unranked_at) {
                    $existingUnlock = PlayerAchievement::where('achievement_id', $achievement->id)
                        ->where('user_id', $winner->user_id);
                    if (!$existingUnlock->exists()) {
                        continue;
                    }
                    $command->info("updating {$winner->user_id}");
                }

                $playerAchievement = PlayerAchievement::updateOrCreate([
                    'achievement_id' => $achievement->id,
                    'user_id' => $winner->user_id,
                ], [
                    'unlocked_at' => $winner->unlocked_hardcore_at,
                    'unlocked_hardcore_at' => $winner->unlocked_hardcore_at,
                ]);

                if ($playerAchievement->wasRecentlyCreated) {
                    $achievement->loadMissing('game');
                    (new AttachPlayerGameAction())->execute($playerAchievement->user, $achievement->game);
                }
            }

            // delete unlocks on the event achievement if the user hasn't unlocked the source achievement.
            // this could affect users who have reset the achievement, but they'll get to keep the badge.
            PlayerAchievement::where('achievement_id', $achievement->id)
                ->whereNotIn('user_id', $winners->pluck('user_id')->toArray())
                ->delete();
        }

        // disable the EventAchievementObserver. we're going to manually populate the associated unlocks.
        $dispatcher = EventAchievement::getEventDispatcher();
        EventAchievement::unsetEventDispatcher();

        // create the event achievement
        $eventAchievement = EventAchievement::updateOrCreate([
            'achievement_id' => $achievement->id,
        ],[
            'source_achievement_id' => $sourceAchievementId,
            'active_from' => $activeFrom,
            'active_through' => $activeThrough,
        ]);

        if ($sourceAchievementId) {
            // make the event achievement look like the source achievement
            $achievement = $eventAchievement->achievement;
            $sourceAchievement = $eventAchievement->sourceAchievement;
            $achievement->title = $sourceAchievement->title;
            $achievement->description = $sourceAchievement->description;
            $achievement->BadgeName = $sourceAchievement->BadgeName;
            $achievement->save();
        }

        // re-enable the EventAchievementObserver
        EventAchievement::setEventDispatcher($dispatcher);

        return $eventAchievement;
    }

    protected function demoteGame(int $gameId): void
    {
        $game = Game::find($gameId);
        if (!str_starts_with($game->Title, "~Z~ ")) {
            $game->Title = "~Z~ {$game->Title}";
            $game->save();
        }

        foreach ($game->gameSets as $gameSet) {
            (new DetachGamesFromGameSetAction())->execute($gameSet, [$gameId]);
        }
    }

    protected function updateMetrics(Event $event): void
    {
        $gameId = $event->legacyGame->id;

        // update metrics and sync to game_achievement_set
        dispatch(new UpdateGameMetricsJob($gameId))->onQueue('game-metrics');

        $playerGames = PlayerGame::where('game_id', $gameId)->get();
        foreach ($playerGames as $playerGame) {
            dispatch(new UpdatePlayerGameMetricsJob($playerGame->user_id, $gameId));
        }

        $game = Game::find($gameId);
    }
}

// Keeps all achievements and unlocks for the game.
// Don't create any tiers.
// Badge only for people who have "mastered" the event.
class ConvertAsIs extends ConvertGame
{
    public function __construct(string $slug, ?string $activeFrom = null, ?string $activeThrough = null, bool $noWinners = false)
    {
        $this->slug = $slug;
        $this->activeFrom = $activeFrom;
        $this->activeThrough = $activeThrough;
        $this->noWinners = $noWinners;
    }
}

// Only keep one achievement and its unlocks. Others are redundant to get the minimum 6 needed for a game mastery.
// Don't create any tiers.
// Badge only for people who have "mastered" the event.
class ConvertCollapse extends ConvertGame
{
    public function __construct(string $slug, ?string $activeFrom = null, ?string $activeThrough = null)
    {
        $this->slug = $slug;
        $this->activeFrom = $activeFrom;
        $this->activeThrough = $activeThrough;
    }

    protected function process(Command $command, Event $event): void
    {
        $first = true;
        foreach ($event->legacyGame->achievements as $achievement) {
            if ($first && $achievement->Flags === AchievementFlag::OfficialCore->value) {
                $first = false;

                $eventAchievement = EventAchievement::where('achievement_id', $achievement->id)->first();
                if (!$eventAchievement) {
                    $eventAchievement = EventAchievement::create(['achievement_id' => $achievement->id]);
                }

                $achievement->Flags = AchievementFlag::OfficialCore->value;
                $achievement->Title = $event->Title;
                if (empty(trim($achievement->Description))) {
                    $achievement->Description = "Earned enough points for the badge";
                }
            } else {
                $achievement->Flags = AchievementFlag::Unofficial->value;
            }

            $achievement->save();
        }
    }
}

// Replace existing achievements with event achievements associated to unlock in a given date range
// Don't create any tiers.
// Badge only for people who have "mastered" the event.
class ConvertToTracked extends ConvertGame
{
    protected array $achievements;

    public function __construct(string $slug, array $achievements)
    {
        $this->slug = $slug;
        $this->achievements = $achievements;
    }

    protected function process(Command $command, Event $event): void
    {
        $this->setAchievementCount($event, count($this->achievements));

        // convert achievements to event achievements
        $index = 0;
        foreach ($this->achievements as $sourceAchievementId => $dates) {
            $achievement = $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->skip($index)->first();
            if (!$achievement) {
                $command->error("Could not find achievement[$index]");

                return;
            }

            $eventAchievement = $this->createDatedEventAchievement($command, $achievement, $sourceAchievementId, $dates);

            if ($index === 0) {
                $event->active_from = $eventAchievement->active_from;
            }

            $index++;
        }

        $event->active_until = $eventAchievement->active_until;
        $event->save();

        $this->updateMetrics($event);
    }

    protected function createDatedEventAchievement(Command $command, Achievement $achievement, ?int $sourceAchievementId, array $dates): EventAchievement
    {
        $eventAchievement = $this->createEventAchievement($command, $achievement, $sourceAchievementId, Carbon::parse($dates[0]), Carbon::parse($dates[1]));

        if (count($dates) >= 3) {
            foreach ($dates[2] as $achievementId) {
                $eventAchievement->source_achievement_id = $achievementId;
                $eventAchievement->save();
            }
        }

        return $eventAchievement;
    }
}

class ConvertToMergedTracked extends ConvertToTracked
{
    protected array $achievements;

    public function __construct(string $slug, $title, array $tiers, array $achievements, array $bonusAchievements = [])
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->tiers = $tiers;
        $this->achievements = $achievements;
        $this->bonusAchievements = $bonusAchievements;
    }

    public function captureBefore(int $gameId): array
    {
        $before = [];

        $tierIndex = 1;
        foreach ($this->tiers as $count => $tierGameId) {
            $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $tierGameId)
                ->orderBy('AwardDataExtra'); // force softcore awards first so they overwritten if the user also has a hardcore award

            foreach ($badges->get() as $badge) {
                $before[$badge->user_id] = [
                    'AwardDate' => $badge->AwardDate,
                    'AwardDataExtra' => $tierIndex,
                ];
            }

            ++$tierIndex;
        }

        return $before;
    }

    protected function convertSiteAwards(Event $event): void
    {
        // do not process site awards here, we'll do it later so we can assign tiers
    }

    protected function process(Command $command, Event $event): void
    {
        $this->setAchievementCount($event, count($this->achievements) + count($this->bonusAchievements));

        // define tiers and update badges
        $tier_index = 1;
        foreach ($this->tiers as $count => $gameId) {
            $eventAward = EventAward::where('event_id', $event->id)
                ->where('tier_index', $tier_index)
                ->first();

            if (!$eventAward) {
                $game = Game::find($gameId);
                $lastSpace = strrpos($game->Title, ' ');

                $eventAward = EventAward::create([
                    'event_id' => $event->id,
                    'tier_index' => $tier_index,
                    'label' => substr($game->Title, $lastSpace + 1),
                    'points_required' => $count,
                    'image_asset_path' => $game->ImageIcon,
                ]);
            }

            PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $gameId)
                ->where('AwardDataExtra', 1)
                ->update([
                    'AwardType' => AwardType::Event,
                    'AwardData' => $event->id,
                    'AwardDataExtra' => $tier_index,
                ]);

            PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $gameId)
                ->where('AwardDataExtra', 0)
                ->delete();

            $tier_index++;
        }

        // convert achievements to event achievements
        $index = 0;
        foreach ($this->achievements as $sourceAchievementId => $dates) {
            $achievement = $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->skip($index)->first();
            if (!$achievement) {
                $command->error("Could not find achievement[$index]");

                return;
            }

            $eventAchievement = $this->createDatedEventAchievement($command, $achievement, $sourceAchievementId, $dates);

            if ($index === 0) {
                $event->active_from = $eventAchievement->active_from;
            }

            $index++;
        }

        $event->active_until = $eventAchievement->active_until;

        foreach ($this->bonusAchievements as $sourceAchievementId => $dates) {
            $achievement = $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->skip($index)->first();
            if (!$achievement) {
                $command->error("Could not find achievement[$index]");

                return;
            }

            if (is_string($sourceAchievementId)) {
                $this->createDatedEventAchievement($command, $achievement, null, $dates);
                $achievement->Title = $sourceAchievementId;
            } else {
                $this->createDatedEventAchievement($command, $achievement, $sourceAchievementId, $dates);
            }

            $achievement->Points = 2;
            $achievement->save();

            $index++;
        }

        if ($eventAchievement->activeUntil > $event->active_until) {
            $event->active_until = $eventAchievement->active_until;
        }

        $event->title = $this->title;
        $event->save();

        $this->updateMetrics($event);
    }
}

// Replace achievements with tiered unlocks awarded to users.
//  Unlocks are awarded as specified in the $achievements array.
//  Users in first entry get all achievements. Users in second entry get all achievements but first.
//  Users in last entry only get last achievement.
// Create the $tiers specified.
//  Tiers are order most prominent to least prominent (gold => silver => bronze)
// Badge people according to number of unlocks they have based on the $tiers.
class ConvertToTiered extends ConvertGame
{
    protected array $tiers;
    protected array $achievements;

    public function __construct(string $slug, array $tiers, array $achievements)
    {
        $this->slug = $slug;
        $this->tiers = $tiers;
        $this->achievements = $achievements;
    }

    protected function convertSiteAwards(Event $event): void
    {
        // do not process site awards here, we'll do it later so we can assign tiers
    }

    public function captureBefore(int $gameId): array
    {
        $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $gameId)
            ->orderBy('AwardDataExtra'); // force softcore awards first so they overwritten if the user also has a hardcore award

        $before = [];
        foreach ($badges->get() as $badge) {
            $before[$badge->user_id] = [
                'AwardDate' => $badge->AwardDate,
                'AwardDataExtra' => 0,
            ];
        }

        // count the number of achievements we expect each user to have
        $allUserIds = [];
        $index = 1;
        foreach ($this->achievements as $achievementId => $users) {
            if ($users === "hardcore_only") {
                $userIds = PlayerAchievement::where('achievement_id', $achievementId)
                    ->whereNotNull('unlocked_hardcore_at')
                    ->pluck('user_id')->toArray();
            } elseif ($users === "to_hardcore") {
                $userIds = PlayerAchievement::where('achievement_id', $achievementId)
                    ->pluck('user_id')->toArray();
            } else {
                $userIds = User::whereIn('User', $users)->pluck('ID')->toArray();
            }

            $allUserIds = array_merge($allUserIds, $userIds);
            foreach ($allUserIds as $userId) {
                if (array_key_exists($userId, $before)) {
                    $before[$userId]['AwardDataExtra'] = $before[$userId]['AwardDataExtra'] + 1;
                }
            }

            ++$index;
        }

        // convert to tiers
        foreach ($before as $userId => &$badge) {
            $tier_index = 1;
            foreach ($this->tiers as $count => $label) {
                if ($count === $badge['AwardDataExtra']) {
                    $badge['AwardDataExtra'] = $tier_index;
                    break;
                }

                if ($count > $badge['AwardDataExtra']) {
                    $badge['AwardDataExtra'] = $tier_index - 1;
                    break;
                }

                $tier_index++;
            }

            if ($badge['AwardDataExtra'] > count($this->tiers)) {
                $badge['AwardDataExtra'] = count($this->tiers);
            }
        }

        return $before;
    }

    protected function process(Command $command, Event $event): void
    {
        $this->setAchievementCount($event, count($this->achievements));

        $tier_counts = [];
        $tier_index = 1;
        foreach ($this->tiers as $count => $label) {
            $eventAward = EventAward::where('event_id', $event->id)
                ->where('tier_index', $tier_index)
                ->first();

            if (!$eventAward) {
                $eventAward = EventAward::create([
                    'event_id' => $event->id,
                    'tier_index' => $tier_index,
                    'label' => $label,
                    'points_required' => $count,
                    'image_asset_path' => $event->image_asset_path,
                ]);
            }

            $tier_counts[] = $count;
            $tier_index++;
        }

        // convert achievements to event achievements
        $tier_index = count($tier_counts);
        $count = count($this->achievements);
        $index = 0;
        $allUserIds = [];
        foreach ($this->achievements as $achievementId => $users) {
            $achievement = Achievement::find($achievementId);
            if (!$achievement) {
                $command->error("Could not find achievement: $achievementId");

                return;
            }

            $this->createEventAchievement($command, $achievement);

            if ($users === 'hardcore_only') {
                // delete any softcore unlocks at this tier
                PlayerAchievement::where('achievement_id', $achievementId)
                    ->whereNull('unlocked_hardcore_at')
                    ->delete();

                // convert hardcore badge to tiered badge
                PlayerBadge::where('AwardType', AwardType::Mastery)
                    ->where('AwardData', $event->legacyGame->id)
                    ->where('AwardDataExtra', 1)
                    ->update([
                        'AwardType' => AwardType::Event,
                        'AwardData' => $event->id,
                        'AwardDataExtra' => $tier_index,
                    ]);
            } else {
                if ($users === 'to_hardcore') {
                    // convert hardcore and softcore badge to tiered badge
                    PlayerBadge::where('AwardType', AwardType::Mastery)
                        ->where('AwardData', $event->legacyGame->id)
                        ->update([
                            'AwardType' => AwardType::Event,
                            'AwardData' => $event->id,
                            'AwardDataExtra' => $tier_index,
                        ]);

                    // fallthrough to convert softcore unlocks to hardcore
                } else {
                    // convert badge to current tier
                    $userIds = User::whereIn('User', $users)->withTrashed()->pluck('ID')->toArray();
                    foreach ($userIds as $userId) {
                        $this->convertBadge($event, $userId, $tier_index);
                    }

                    // keep track of users eligible for later achievements
                    $allUserIds = array_merge($allUserIds, $userIds);

                    // delete all unlocks for users not at this tier
                    PlayerAchievement::where('achievement_id', $achievementId)
                        ->whereNotIn('user_id', $allUserIds)
                        ->delete();

                    // fallthrough to convert softcore unlocks to hardcore
                }

                // update any softcore unlocks at this tier to hardcore
                PlayerAchievement::where('achievement_id', $achievementId)
                    ->whereNull('unlocked_hardcore_at')
                    ->update(['unlocked_hardcore_at' => DB::raw('unlocked_at')]);
            }

            // update tier_index if crossing a threshold
            if ($tier_index > 0 && $tier_counts[$tier_index - 1] === $count) {
                $tier_index--;
            }

            $count--;
            $index++;

            $achievement->DisplayOrder = $index;
            $achievement->save();
        }

        $this->updateMetrics($event);

        // delete any remaining badges
        PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 0)
            ->delete();
    }

    protected function convertBadge(Event $event, int $userId, int $tier_index): void
    {
        // find hardcore badge
        $badge = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 1)
            ->where('user_id', $userId)
            ->first();

        if (!$badge) {
            // hardcore badge not found, look for softcore badge
            $badge = PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $event->legacyGame->id)
                ->where('AwardDataExtra', 0)
                ->where('user_id', $userId)
                ->first();
        }

        if ($badge) {
            // convert found badge
            $badge->AwardType = AwardType::Event;
            $badge->AwardData = $event->id;
            $badge->AwardDataExtra = $tier_index;
            $badge->save();
        }
    }
}

// Keep all the existing achievements for the hardcore baadge
//  Add an additional achievement for users who only earned the softcore badge.
// Create two tiers - one for the softcore badge and one for the hardcore badge.
// Map players with the softcore badge to the softcore achievement and softcore badge.
// Map players with the hardcore badge to all achievements and the hardcore badge.
class ConvertToSoftcoreTiered extends ConvertGame
{
    protected string $softcoreLabel;
    protected string $hardcoreLabel;
    protected int $hardcoreAchievementId;

    public function __construct(string $slug, string $softcoreLabel, string $hardcoreLabel, int $hardcoreAchievementId = 0)
    {
        $this->slug = $slug;
        $this->softcoreLabel = $softcoreLabel;
        $this->hardcoreLabel = $hardcoreLabel;
        $this->hardcoreAchievementId = $hardcoreAchievementId;
    }

    protected function convertSiteAwards(Event $event): void
    {
        // softcore badge -> tier 1, hardcore badge -> tier 2
        $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id);

        foreach ($badges->get() as $badge) {
            $badge->AwardType = AwardType::Event;
            $badge->AwardData = $event->id;
            $badge->AwardDataExtra = ($badge->AwardDataExtra === 1) ? 2 : 1;
            $badge->save();
        }
    }

    public function captureBefore(int $gameId): array
    {
        $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $gameId)
            ->orderBy('AwardDataExtra'); // force softcore awards first so they overwritten if the user also has a hardcore award

        $before = [];
        foreach ($badges->get() as $badge) {
            $before[$badge->user_id] = [
                'AwardDate' => $badge->AwardDate,
                'AwardDataExtra' => ($badge->AwardDataExtra === 1) ? 2 : 1,
            ];
        }

        return $before;
    }

    protected function process(Command $command, Event $event): void
    {
        $description = "Earn $this->softcoreLabel";
        $winnerAchievement = ($this->hardcoreAchievementId)
            ? Achievement::find($this->hardcoreAchievementId)
            : Achievement::where('GameID', $event->legacyGame->id)->where('Description', $description)->first();
        if (!$winnerAchievement) {
            $winnerAchievement = Achievement::create([
                'Title' => 'Winner',
                'Description' => $description,
                'Points' => 1,
                'MemAddr' => '0=1',
                'Flags' => AchievementFlag::OfficialCore->value,
                'GameID' => $event->legacyGame->id,
                'user_id' => EventAchievement::RAEVENTS_USER_ID,
                'BadgeName' => '00000',
                'DisplayOrder' => $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->count() + 1,
            ]);
        }

        if (!EventAward::where('event_id', $event->id)->where('tier_index', 1)->exists()) {
            $eventAward = EventAward::create([
                'event_id' => $event->id,
                'tier_index' => 1,
                'label' => $this->softcoreLabel,
                'points_required' => 1,
                'image_asset_path' => $event->image_asset_path,
            ]);
        }

        if (!EventAward::where('event_id', $event->id)->where('tier_index', 2)->exists()) {
            $eventAward = EventAward::create([
                'event_id' => $event->id,
                'tier_index' => 2,
                'label' => $this->hardcoreLabel,
                'points_required' => $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->count(),
                'image_asset_path' => $event->image_asset_path,
            ]);
        }

        // convert achievements to event achievements
        $first = true;
        foreach ($event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value) as $achievement) {
            $this->createEventAchievement($command, $achievement);

            if ($first) {
                $first = false;

                if (!PlayerAchievement::where('achievement_id', $winnerAchievement->id)->exists()) {
                    foreach (PlayerAchievement::where('achievement_id', $achievement->id)->get() as $playerAchievement) {
                        if ($playerAchievement->unlocked_hardcore_at) {
                            // duplicate any hardcore unlocks for this achievement to the Winner achievement
                            PlayerAchievement::create([
                                'user_id' => $playerAchievement->user_id,
                                'achievement_id' => $winnerAchievement->id,
                                'unlocker_id' => $playerAchievement->unlocker_id,
                                'unlocked_at' => $playerAchievement->unlocked_at,
                                'unlocked_hardcore_at' => $playerAchievement->unlocked_hardcore_at,
                            ]);                            
                        } else {
                            // convert any softcore unlocks for this achievement to hardcore unlocks for the Winner achievement
                            $playerAchievement->unlocked_hardcore_at = $playerAchievement->unlocked_at;
                            $playerAchievement->achievement_id = $winnerAchievement->id;
                            $playerAchievement->save();
                        }
                    }
                }
            }

            // delete any softcore unlocks for this achievement
            PlayerAchievement::where('achievement_id', $achievement->id)
                ->whereNull('unlocked_hardcore_at')
                ->delete();
        }

        $this->updateMetrics($event);
    }
}


// Replace achievements with tiered unlocks awarded to users.
//  Unlocks are redistributed to ensure each player receives the correct tier.
//  The first achievement is awarded to all people.
//  The last achievement is only awarded to people receiving the last tier.
// Create the $tiers specified.
//  Tiers are ordered least prominent to most prominent (bronze => silver => gold)
// Badge people according to number of unlocks they have based on the $tiers.
class ConvertToCollapsedTiered extends ConvertToTiered
{
    protected string $title;

    public function __construct(string $slug, string $title, array $tiers, array $tierAchievements)
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->tiers = $tiers;
        $this->achievements = $tierAchievements;
    }

    protected function convertSiteAwards(Event $event): void
    {
        // do not process site awards here, we'll do it later so we can assign tiers
    }


    public function captureBefore(int $gameId): array
    {
        $before = [];

        $tierIndex = 1;
        foreach ($this->tiers as $tierGameId => $title) {
            $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $tierGameId)
                ->orderBy('AwardDataExtra'); // force softcore awards first so they overwritten if the user also has a hardcore award

            $before = [];
            foreach ($badges->get() as $badge) {
                $before[$badge->user_id] = [
                    'AwardDate' => $badge->AwardDate,
                    'AwardDataExtra' => $tierIndex,
                ];
            }

            ++$tierIndex;
        }

        return $before;
    }

    protected function process(Command $command, Event $event): void
    {
        // move the achievements from the tier games into the base game
        $labels = [];
        foreach ($this->tiers as $gameId => $label) {
            $labels[] = $label;
            if ($gameId != $event->legacyGame->id) {
                Achievement::where('GameID', $gameId)->update(['GameID' => $event->legacyGame->id]);
            }
        }

        Achievement::where('GameID', $event->legacyGame->id)
            ->whereNotIn('ID', $this->achievements)
            ->update(['Flags' => AchievementFlag::Unofficial->value]);
        Achievement::whereIn('ID', $this->achievements)
            ->update(['Flags' => AchievementFlag::OfficialCore->value]);

        $eventGameUserIds = PlayerGame::where('game_id', $event->legacyGame->id)->pluck('user_id')->toArray();

        $tier_index = 1;
        foreach ($this->tiers as $gameId => $label) {
            $eventAward = EventAward::where('event_id', $event->id)
                ->where('tier_index', $tier_index)
                ->first();

            if (!$eventAward) {
                $eventAward = EventAward::create([
                    'event_id' => $event->id,
                    'tier_index' => $tier_index,
                    'label' => $label,
                    'points_required' => $tier_index,
                    'image_asset_path' => Game::find($gameId)->ImageIcon,
                ]);
            }

            // migrate the player game metrics to the merged game
            if ($gameId != $event->legacyGame->id) {
                PlayerGame::where('game_id', $gameId)
                    ->whereNotIn('user_id', $eventGameUserIds)
                    ->update(['game_id' => $event->legacyGame->id]);
            }

            $tier_index++;
        }

        // convert achievements to event achievements
        $index = 0;
        foreach ($this->achievements as $achievementId) {
            $achievement = Achievement::find($achievementId);
            if (!$achievement) {
                $command->error("Could not find achievement: $achievementId");

                return;
            }

            $achievement->Title = $this->title;
            $achievement->Description = $labels[$index];
            $achievement->DisplayOrder = $index + 1;
            $achievement->save();

            $index++;
        }

        $tier_index = count($this->tiers);
        $count = count($this->achievements);
        $allUserIds = [];
        $previousAchievementIds = [];
        foreach (array_reverse($this->achievements) as $achievementId) {
            $userIds = $achievement->playerAchievements()->pluck('user_id')->toArray();
            foreach ($userIds as $userId) {
                $this->convertBadge($event, $userId, $tier_index);
            }

            // reassign unlocks from a secondary achievement in the first tier set to the primary achievement in the new tier set
            if (!empty($allUserIds)) {
                $newPreviousAchievementIds = [];
                foreach ($previousAchievementIds as $previousAchievementId) {
                    PlayerAchievement::where('achievement_id', $previousAchievementId)
                        ->whereIn('user_id', $allUserIds)
                        ->update(['achievement_id' => $achievementId]);
                    $newPreviousAchievementIds[] = $previousAchievementId + 1;
                }
                $previousAchievementIds = $newPreviousAchievementIds;
            }
            $previousAchievementIds[] = $achievementId + 1;

            $allUserIds = array_unique(array_merge($allUserIds, $userIds));

            // delete all unlocks for users not at this tier
            PlayerAchievement::where('achievement_id', $achievementId)
                ->whereNotIn('user_id', $allUserIds)
                ->delete();

            // update any softcore unlocks at this tier to hardcore
            PlayerAchievement::where('achievement_id', $achievementId)
                ->whereNull('unlocked_hardcore_at')
                ->update(['unlocked_hardcore_at' => DB::raw('unlocked_at')]);

            $tier_index--;
        }

        $event->legacyGame->achievements_published = count($this->achievements);
        $event->legacyGame->Title = $this->title;
        $event->legacyGame->save();

        // delete any remaining badges
        PlayerBadge::where('AwardType', AwardType::Mastery)
            ->where('AwardData', $event->legacyGame->id)
            ->where('AwardDataExtra', 0)
            ->delete();

        // ensure PlayerGame records exist for all specified users
        foreach ($allUserIds as $userId) {
            (new AttachPlayerGameAction())->execute(User::find($userId), $event->legacyGame);
        }

        $this->updateMetrics($event);

        // update the merged games to be ~Z~ records and disconnect them from all hubs
        foreach ($this->tiers as $gameId => $label) {
            if ($gameId != $event->legacyGame->id) {
                $this->demoteGame($gameId);
            }
        }
    }
}

class ConvertAotWTiered extends ConvertGame
{
    protected array $tiers;
    protected array $achievements;
    protected ?array $aotm_achievements;
    protected bool $extraDay;

    public function __construct(string $slug, string $activeFrom, array $tiers,
                                array $achievements, ?array $aotm_achievements = null, bool $extraDay = false)
    {
        $this->slug = $slug;
        $this->activeFrom = $activeFrom;
        $this->tiers = $tiers;
        $this->achievements = $achievements;
        $this->aotm_achievements = $aotm_achievements;
        $this->extraDay = $extraDay;
    }

    public function captureBefore(int $gameId): array
    {
        $before = [];

        $tierIndex = 1;
        foreach ($this->tiers as $count => $tierGameId) {
            $badges = PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $tierGameId)
                ->orderBy('AwardDataExtra'); // force softcore awards first so they overwritten if the user also has a hardcore award

            foreach ($badges->get() as $badge) {
                $before[$badge->user_id] = [
                    'AwardDate' => $badge->AwardDate,
                    'AwardDataExtra' => $tierIndex,
                ];
            }

            ++$tierIndex;
        }

        return $before;
    }

    protected function convertSiteAwards(Event $event): void
    {
        // do not process site awards here, we'll do it later so we can assign tiers
    }

    protected function process(Command $command, Event $event): void
    {
        // move the achievements from the tier games into the base game
        foreach ($this->tiers as $count => $gameId) {
            if ($gameId != $event->legacyGame->id) {
                Achievement::where('GameID', $gameId)->update(['GameID' => $event->legacyGame->id]);
            }
        }

        $this->setAchievementCount($event, count($this->achievements) + count($this->aotm_achievements ?? []));

        // define tiers and update badges
        $tier_index = 1;
        foreach ($this->tiers as $count => $gameId) {
            $eventAward = EventAward::where('event_id', $event->id)
                ->where('tier_index', $tier_index)
                ->first();

            if (!$eventAward) {
                $game = Game::find($gameId);
                $lastSpace = strrpos($game->Title, ' ');

                $eventAward = EventAward::create([
                    'event_id' => $event->id,
                    'tier_index' => $tier_index,
                    'label' => substr($game->Title, $lastSpace + 1),
                    'points_required' => $count,
                    'image_asset_path' => $game->ImageIcon,
                ]);
            }

            PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $gameId)
                ->where('AwardDataExtra', 1)
                ->update([
                    'AwardType' => AwardType::Event,
                    'AwardData' => $event->id,
                    'AwardDataExtra' => $tier_index,
                ]);

            PlayerBadge::where('AwardType', AwardType::Mastery)
                ->where('AwardData', $gameId)
                ->where('AwardDataExtra', 0)
                ->delete();

            $tier_index++;
        }

        $date = Carbon::parse($this->activeFrom);
        $year = $date->clone()->addWeeks(1)->year;

        $event->legacyGame->Title = "Achievement of the Week $year";
        $event->legacyGame->save();

        $event->active_from = $date;

        // convert achievements to event achievements
        $index = 0;
        foreach ($this->achievements as $sourceAchievementId) {
            $endDate = $date->clone()->addDays($this->extraDay ? 7 : 6);

            $achievement = $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->skip($index)->first();
            $eventAchievement = $this->createEventAchievement($command, $achievement, $sourceAchievementId, $date, $endDate);

            $date = $date->addDays(7);
            $index++;
        }

        while ($date->year === $year) {
            $date = $date->addWeeks(1);
        }
        $event->active_until = $date;

        if ($this->aotm_achievements) {
            $date = Carbon::parse($this->activeFrom);
            foreach ($this->aotm_achievements as $sourceAchievementId) {
                $endDate = $date->clone()->addWeeks(3)->subDays(1);
                while ($endDate->month === $date->month) {
                    $endDate = $endDate->addWeeks(1);
                }

                $achievement = $event->legacyGame->achievements->where('Flags', AchievementFlag::OfficialCore->value)->skip($index)->first();

                if (is_array($sourceAchievementId)) {
                    $eventAchievement = EventAchievement::where('achievement_id', $achievement->id)->first();
                    if (!in_array($eventAchievement->source_achievement_id ?? 0, $sourceAchievementId)) {
                        foreach ($sourceAchievementId as $id) {
                            $eventAchievement = $this->createEventAchievement($command, $achievement, $id, $date, $endDate);
                        }
                    }
                } else {
                    $eventAchievement = $this->createEventAchievement($command, $achievement, $sourceAchievementId, $date, $endDate);
                }

                $date = $endDate->addDays(1);
                $index++;
            }
        }

        $event->save();

        $this->updateMetrics($event);

        // update the merged games to be ~Z~ records and disconnect them from all hubs
        foreach ($this->tiers as $count => $gameId) {
            if ($gameId != $event->legacyGame->id) {
                $this->demoteGame($gameId);
            }
        }
    }
}
