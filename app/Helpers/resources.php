<?php

declare(strict_types=1);

use App\Platform\Models\Achievement;
use App\Platform\Models\Emulator;
use App\Platform\Models\Game;
use App\Platform\Models\Leaderboard;
use App\Platform\Models\System;
use App\Site\Models\User;

if (!function_exists('__res')) {
    function __res(string $resource = 'resource', int $amount = 2): array|string
    {
        return trans('resource.title', ['resource' => trans_choice("resource.$resource.title", $amount)]);
    }
}

if (!function_exists('resource_type')) {
    function resource_type(mixed $modelClass): ?string
    {
        if ($modelClass instanceof Illuminate\Database\Eloquent\Model) {
            $modelClass = $modelClass::class;
        }

        $resourceType = array_search($modelClass, Illuminate\Database\Eloquent\Relations\Relation::$morphMap);

        return is_string($resourceType) ? $resourceType : null;
    }
}

if (!function_exists('resource_class')) {
    function resource_class(string $modelType): mixed
    {
        return Illuminate\Database\Eloquent\Relations\Relation::$morphMap[$modelType] ?? null;
    }
}

// if (!function_exists('achievement')) {
//     function achievement_avatar(Achievement $achievement, string $display = 'name', string $iconSize = 'sm'): string
//     {
//         return view('components.achievement.avatar', ['achievement' => $achievement, 'display' => $display, 'iconSize' => $iconSize])
//             ->render();
//     }
// }
//
// if (!function_exists('system_avatar')) {
//     function system_avatar(System $system, string $display = 'name_short', string $iconSize = 'sm'): string
//     {
//         return view('components.system.avatar', ['system' => $system, 'display' => $display, 'iconSize' => $iconSize])
//             ->render();
//     }
// }
//
// if (!function_exists('emulator')) {
//     function emulator_avatar(Emulator $emulator, string $display = 'name', string $iconSize = 'sm'): string
//     {
//         return view('components.emulator.avatar', ['emulator' => $emulator, 'display' => $display, 'iconSize' => $iconSize])
//             ->render();
//     }
// }
//
// if (!function_exists('game')) {
//     function game_avatar(Game $game, string $display = 'name', string $iconSize = 'sm'): string
//     {
//         return view('components.game.avatar', ['game' => $game, 'display' => $display, 'iconSize' => $iconSize])
//             ->render();
//     }
// }
//
// if (!function_exists('leaderboard')) {
//     function leaderboard_avatar(Leaderboard $leaderboard, string $display = 'name'): string
//     {
//         return view('components.leaderboard.avatar', ['leaderboard' => $leaderboard, 'display' => $display])
//             ->render();
//     }
// }

if (!function_exists('user')) {
    function user_avatar(User $user, string $display = 'name', string $iconSize = 'sm'): string
    {
        return view('components.user.avatar', ['user' => $user, 'display' => $display, 'iconSize' => $iconSize])
            ->render();
    }
}
