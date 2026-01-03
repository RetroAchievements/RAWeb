<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmulatorResource\RelationManagers;

use App\Models\System;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SystemsRelationManager extends RelationManager
{
    protected static string $relationship = 'systems';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->systems->count();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(DB::raw('systems.id'), 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('name_full')
                    ->label('Full name')
                    ->description(fn (System $record): ?string => $record->name_short)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(DB::raw('systems.name_full'), 'like', "%{$search}%");
                    })
                    ->sortable()
                    ->grow(true),
            ])
            ->filters([

            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('name_full');
            });
    }
}
