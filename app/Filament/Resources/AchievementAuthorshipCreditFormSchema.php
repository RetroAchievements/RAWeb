<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\User;
use App\Platform\Enums\AchievementAuthorTask;
use Filament\Forms;

class AchievementAuthorshipCreditFormSchema
{
    public static function getSchema(): array
    {
        return [
            Forms\Components\Select::make('task')
                ->options(
                    collect(AchievementAuthorTask::cases())
                        ->mapWithKeys(fn ($enum) => [$enum->value => $enum->label()])
                )
                ->required(),

            Forms\Components\Select::make('user_id')
                ->label('User')
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    $lowercased = strtolower($search);

                    return User::whereRaw('LOWER(User) = ?', [$lowercased])
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
                ->getOptionLabelUsing(fn (int $value): string => User::find($value)?->display_name ?? 'Deleted User')
                ->required(),

            Forms\Components\DateTimePicker::make('created_at')
                ->label('Date Credited')
                ->default(now()),
        ];
    }
}
