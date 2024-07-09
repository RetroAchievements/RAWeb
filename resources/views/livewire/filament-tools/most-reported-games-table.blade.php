<?php

use App\Models\Role;
use App\Models\Ticket;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;

new class extends Component implements HasForms, HasTable {
    use InteractsWithTable;
    use InteractsWithForms;

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
        $oldestTicketSubquery = Ticket::unresolved()
            ->officialCore()
            ->select('AchievementID', DB::raw('MIN(ReportedAt) as OldestTicketDate'))
            ->groupBy('AchievementID');

        return (
            Ticket::unresolved()
                ->officialCore()
                ->join('Achievements', 'Achievements.ID', '=', 'Ticket.AchievementID')
                ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
                ->join('Console', 'Console.ID', '=', 'GameData.ConsoleID')
                ->leftJoinSub($oldestTicketSubquery, 'oldest_tickets', function ($join) {
                    $join->on('Ticket.AchievementID', '=', 'oldest_tickets.AchievementID');
                })
                ->select(
                    'GameData.ID',
                    'GameData.Title',
                    'GameData.ConsoleID',
                    'GameData.ImageIcon',
                    'GameData.players_total',
                    'Console.Name as ConsoleName',
                    DB::raw('count(Ticket.ID) AS TicketCount'),
                    DB::raw('count(DISTINCT Ticket.AchievementID) AS UniquelyTicketedAchievements'),
                    DB::raw('MIN(oldest_tickets.OldestTicketDate) AS OldestTicketDate'),
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
</div>
