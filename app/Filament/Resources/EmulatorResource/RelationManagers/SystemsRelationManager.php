<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use App\Models\System;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        return $table
            ->recordTitleAttribute('name_full')
            ->columns([
                Tables\Columns\ImageColumn::make('icon_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('Console.ID', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('name_full')
                    ->label('Full name')
                    ->description(fn (System $record): ?string => $record->name_short)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('Console.name_full', 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->grow(true),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('name_full');
            });
    }
}
