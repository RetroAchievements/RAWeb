<?php

use App\Models\Role;
use App\Models\TriggerTicket;
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
                    ->url(fn ($record) => route('game.show', ['game' => $record->ID])),

                Tables\Columns\TextColumn::make('Title')
                    ->description(fn ($record) => $record->ConsoleName)
                    ->url(fn ($record) => route('game.show', ['game' => $record->ID])),

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
                    ->url(fn ($record) => route('game.tickets', ['game' => $record->ID, 'filter[achievement]' => 'core'])),

                Tables\Columns\TextColumn::make('TicketCount')
                    ->label('Open Tickets')
                    ->numeric()
                    ->url(fn ($record) => route('game.tickets', ['game' => $record->ID, 'filter[achievement]' => 'core']))
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
        $oldestTicketSubquery = TriggerTicket::unresolved()
            ->officialCore()
            ->select('ticketable_id', DB::raw('MIN(created_at) as OldestTicketDate'))
            ->groupBy('ticketable_id');

        $newestTicketSubquery = TriggerTicket::unresolved()
            ->officialCore()
            ->select('ticketable_id', DB::raw('MAX(created_at) as NewestTicketDate'))
            ->groupBy('ticketable_id');

        return (
            TriggerTicket::unresolved()
                ->officialCore()
                ->join('Achievements', 'Achievements.ID', '=', 'trigger_tickets.ticketable_id')
                ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
                ->join('Console', 'Console.ID', '=', 'GameData.ConsoleID')
                ->leftJoinSub($oldestTicketSubquery, 'oldest_tickets', function ($join) {
                    $join->on('trigger_tickets.ticketable_id', '=', 'oldest_tickets.ticketable_id');
                })
                ->leftJoinSub($newestTicketSubquery, 'newest_tickets', function ($join) {
                    $join->on('trigger_tickets.ticketable_id', '=', 'newest_tickets.ticketable_id');
                })
                ->select(
                    DB::raw('MIN(trigger_tickets.id) as id'),
                    'GameData.ID',
                    'GameData.Title',
                    'GameData.ConsoleID',
                    'GameData.ImageIcon',
                    'GameData.players_total',
                    'Console.Name as ConsoleName',
                    DB::raw('count(trigger_tickets.id) AS TicketCount'),
                    DB::raw('count(DISTINCT trigger_tickets.ticketable_id) AS UniquelyTicketedAchievements'),
                    DB::raw('MIN(oldest_tickets.OldestTicketDate) AS OldestTicketDate'),
                    DB::raw('MAX(newest_tickets.NewestTicketDate) AS NewestTicketDate'),
                )
                ->groupBy(
                    'GameData.ID',
                    'GameData.Title',
                    'GameData.ConsoleID',
                    'Console.Name',
                    'GameData.ImageIcon',
                    'GameData.players_total',
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
