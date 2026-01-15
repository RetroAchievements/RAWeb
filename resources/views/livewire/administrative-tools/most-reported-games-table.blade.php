<?php

use App\Models\Role;
use App\Models\Ticket;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component implements HasForms, HasTable, HasActions {
    use InteractsWithTable;
    use InteractsWithForms;
    use InteractsWithActions;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->buildMostTicketedSetsQuery())
            ->paginated(false)
            ->columns([
                Tables\Columns\ImageColumn::make('ImageIcon')
                    ->label('')
                    ->getStateUsing(fn ($record) => media_asset($record->ImageIcon))
                    ->size(config('media.icon.sm.width'))
                    ->url(fn ($record) => route('game.show', ['game' => $record->id])),

                Tables\Columns\TextColumn::make('Title')
                    ->description(fn ($record) => $record->ConsoleName)
                    ->url(fn ($record) => route('game.show', ['game' => $record->id])),

                Tables\Columns\TextColumn::make('players_total')
                    ->label('Player Count')
                    ->numeric(),

                Tables\Columns\TextColumn::make('OldestTicketDate')
                    ->label('Tickets Opened Since')
                    ->since(),

                Tables\Columns\TextColumn::make('NewestTicketDate')
                    ->label('Newest Ticket Date')
                    ->since(),

                Tables\Columns\TextColumn::make('UniquelyTicketedAchievements')
                    ->label('Ticketed Achievements')
                    ->numeric()
                    ->url(fn ($record) => route('game.tickets', ['game' => $record->id, 'filter[achievement]' => 'core'])),

                Tables\Columns\TextColumn::make('TicketCount')
                    ->label('Open Tickets')
                    ->numeric()
                    ->url(fn ($record) => route('game.tickets', ['game' => $record->id, 'filter[achievement]' => 'core']))
                    ->color(fn ($record) => $record->TicketCount >= 10 ? 'danger' : null),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    private function buildMostTicketedSetsQuery(): Builder
    {
        $oldestTicketSubquery = Ticket::unresolved()
            ->officialCore()
            ->select('ticketable_id', DB::raw('MIN(created_at) as OldestTicketDate'))
            ->groupBy('ticketable_id');

        $newestTicketSubquery = Ticket::unresolved()
            ->officialCore()
            ->select('ticketable_id', DB::raw('MAX(created_at) as NewestTicketDate'))
            ->groupBy('ticketable_id');

        return (
            Ticket::unresolved()
                ->officialCore()
                ->join('achievements', 'achievements.id', '=', 'tickets.ticketable_id')
                ->join('games', 'games.id', '=', 'achievements.game_id')
                ->join('systems', 'systems.id', '=', 'games.system_id')
                ->leftJoinSub($oldestTicketSubquery, 'oldest_tickets', function ($join) {
                    $join->on('tickets.ticketable_id', '=', 'oldest_tickets.ticketable_id');
                })
                ->leftJoinSub($newestTicketSubquery, 'newest_tickets', function ($join) {
                    $join->on('tickets.ticketable_id', '=', 'newest_tickets.ticketable_id');
                })
                ->select(
                    'games.id as id',
                    'games.title as Title',
                    'games.system_id as ConsoleID',
                    'games.image_icon_asset_path as ImageIcon',
                    'games.players_total',
                    'systems.name as ConsoleName',
                    DB::raw('count(tickets.id) AS TicketCount'),
                    DB::raw('count(DISTINCT tickets.ticketable_id) AS UniquelyTicketedAchievements'),
                    DB::raw('MIN(oldest_tickets.OldestTicketDate) AS OldestTicketDate'),
                    DB::raw('MAX(newest_tickets.NewestTicketDate) AS NewestTicketDate'),
                )
                ->groupBy(
                    'games.id',
                    'games.title',
                    'games.system_id',
                    'systems.name',
                    'games.image_icon_asset_path',
                    'games.players_total',
                )
                ->orderBy('TicketCount', 'DESC')
                ->limit(100)
        );
    }
}

?>

<div>
    {{ $this->table }}
    <x-filament-actions::modals />
</div>
