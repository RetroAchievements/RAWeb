<?php

use App\Community\Enums\TicketState;
use App\Models\Role;
use App\Models\Ticket;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\TicketableType;
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
use Livewire\Component;

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

                Tables\Columns\TextColumn::make('UniquelyTicketedItems')
                    ->label('Ticketed Items')
                    ->numeric()
                    ->url(fn ($record) => route('game.tickets', ['game' => $record->id])),

                Tables\Columns\TextColumn::make('TicketCount')
                    ->label('Open Tickets')
                    ->numeric()
                    ->url(fn ($record) => route('game.tickets', ['game' => $record->id]))
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
        return Ticket::query()
            ->whereIn('tickets.state', [TicketState::Open, TicketState::Request]) // don't collide with leaderboards.state
            ->leftJoin('achievements', function ($join) {
                $join->on('achievements.id', '=', 'tickets.ticketable_id')
                    ->where('tickets.ticketable_type', TicketableType::Achievement->value)
                    ->where('achievements.is_promoted', true);
            })
            ->leftJoin('leaderboards', function ($join) {
                $join->on('leaderboards.id', '=', 'tickets.ticketable_id')
                    ->where('tickets.ticketable_type', TicketableType::Leaderboard->value)
                    ->where('leaderboards.state', '!=', LeaderboardState::Unpromoted->value);
            })
            ->join('games', function ($join) {
                $join->on('games.id', '=', DB::raw('COALESCE(achievements.game_id, leaderboards.game_id)'));
            })
            ->join('systems', 'systems.id', '=', 'games.system_id')
            ->select(
                'games.id as id',
                'games.title as Title',
                'games.system_id as ConsoleID',
                'games.image_icon_asset_path as ImageIcon',
                'games.players_total',
                'systems.name as ConsoleName',
                DB::raw('count(tickets.id) AS TicketCount'),
                DB::raw('count(DISTINCT tickets.ticketable_type, tickets.ticketable_id) AS UniquelyTicketedItems'),
                DB::raw('MIN(tickets.created_at) AS OldestTicketDate'),
                DB::raw('MAX(tickets.created_at) AS NewestTicketDate'),
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
            ->limit(100);
    }
}

?>

<div>
    {{ $this->table }}
    <x-filament-actions::modals />
</div>
