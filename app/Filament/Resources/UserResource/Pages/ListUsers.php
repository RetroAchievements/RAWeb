<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    // public function getTabs(): array
    // {
    //     return [
    //         'all' => Tab::make(),
    //         'tracked' => Tab::make()
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('Untracked', false)),
    //         'untracked' => Tab::make()
    //             ->modifyQueryUsing(fn (Builder $query) => $query->where('Untracked', true)),
    //     ];
    // }
}
