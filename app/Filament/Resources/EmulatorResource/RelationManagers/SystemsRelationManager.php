<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use App\Models\Emulator;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SystemsRelationManager extends RelationManager
{
    protected static string $relationship = 'systems';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = auth()->user();

        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),
                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_full')
                    ->label('Full name')
                    ->description(fn (System $record): ?string => $record->name_short)
                    ->searchable()
                    ->sortable()
                    ->grow(true),
            ])
            ->filters([

            ])
            ->headerActions([

            ])
            ->actions([

            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('name_full');
            });
    }
}
