<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\User;
use App\Platform\Enums\AchievementAuthorTask;
use App\Policies\AchievementAuthorPolicy;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class AchievementAuthorshipCreditFormSchema
{
    public static function getSchema(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $allowedTasks = collect(AchievementAuthorTask::cases())
            ->filter(function ($task) use ($user) {
                return (new AchievementAuthorPolicy())->canUpsertTask($user, $task);
            })
            ->mapWithKeys(fn ($enum) => [$enum->value => $enum->label()]);

        return [
            Forms\Components\Select::make('task')
                ->options($allowedTasks)
                ->required(),

            Forms\Components\Select::make('user_id')
                ->label('User')
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    $lowercased = strtolower($search);

                    return User::withTrashed()
                        ->whereRaw('LOWER(User) = ?', [$lowercased])
                        ->orWhere(function ($query) use ($lowercased) {
                            $query->whereRaw('LOWER(display_name) like ?', ["%{$lowercased}%"])
                                ->orWhereRaw('LOWER(User) like ?', ["%{$lowercased}%"]);
                        })
                        ->orderByRaw('LOWER(User) = ? DESC', [$lowercased])
                        ->limit(50)
                        ->get()
                        ->pluck('display_name', 'id')
                        ->toArray();
                })
                ->getOptionLabelUsing(fn (int $value): string => User::withTrashed()->find($value)?->display_name ?? 'Deleted User')
                ->required(),

            Forms\Components\DateTimePicker::make('created_at')
                ->label('Date Credited')
                ->default(now()),
        ];
    }
}
