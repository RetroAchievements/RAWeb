<?php

use App\Enums\Permissions;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;

new class extends Component implements HasForms {
    use InteractsWithForms;

    public ?int $userId = null;
    public ?string $username = null;
    public array $altsFound = [];

    public function submit(): void
    {
        $this->form->getState();

        $forUser = User::withTrashed()->find($this->userId);
        if (!$forUser) {
            Notification::make()
                ->danger()
                ->title('Failed')
                ->body('Unknown user')
                ->send();

            return;
        }

        $emailAddresses = [];
        if (!empty($forUser->email)) {
            $emailAddresses[] = $forUser->email;
        }
        if (!empty($forUser->email_original) && $forUser->email_original != $forUser->email) {
            $emailAddresses[] = $forUser->email_original;
        }

        $this->username = $forUser->display_name;
        $this->altsFound = User::withTrashed()
            ->where(function ($query) use ($emailAddresses) {
                $query->whereIn('email', $emailAddresses)
                    ->orWhereIn('email_original', $emailAddresses);
            })
            ->pluck('id')
            ->toArray();

        $this->dispatch('open-modal', id: 'alts-found-modal');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('userId')
                    ->label('Username')
                    ->getOptionLabelUsing(function (int $value): string {
                        $user = User::withTrashed()->where('id', $value)->first();

                        return $user?->display_name ?? '[Unknown user]';
                    })
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search): array {
                        return User::search($search)
                            ->withTrashed()
                            ->take(50)
                            ->get()
                            ->mapWithKeys(function ($user) {
                                return [$user->id => $user->display_name === $user->username ? $user->display_name : "{$user->display_name} ($user->username)"];
                            })
                            ->toArray();
                    })
                    ->statePath('userId')
                    ->required()
                    ->live(),
            ]);
    }
}

?>

<div>
    <form wire:submit.prevent="submit">
        <div class="flex flex-col gap-y-4">
            {{ $this->form }}

            <div class="flex w-full justify-end">
                <x-filament::button type="submit">Submit</x-filament::button>
            </div>
        </div>
    </form>

    <x-filament::modal id="alts-found-modal" width="4xl">
        <x-slot name="heading">@if (count($this->altsFound) < 2)No @endif Alts Found</x-slot>
            @php
                $alts = User::withTrashed()->whereIn('id', $this->altsFound)->orderByDesc('last_activity_at')->get();
            @endphp
            @if (count($alts) < 2)
                <span class="text-sm font-medium text-gray-950 dark:text-white">No other users share the same email address as {{ $this->username }}.</span>
            @else
                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ count($this->altsFound) }} users share the same email address as {{ $this->username }}.</span>
                <table class="fi-ta-table w-full text-sm text-left">
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @foreach($alts as $user)
                            <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/10 transition" onclick="window.open('{{ route('user.show', $user->display_name) }}', '_blank')">
                                <td class="fi-ta-cell px-4 py-3 align-top w-full min-w-128">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->display_name }}" class="w-10 h-10 rounded-xs object-contain bg-white dark:bg-gray-900 ring-1 ring-gray-950/10 dark:ring-white/20" onerror="this.style.display='none'">
                                        <div class="flex flex-col">
                                            @if ($user->deleted_at)
                                                <span class="line-through">{{ $user->display_name }}</span>
                                            @else
                                                <a href="{{ route('user.show', $user->display_name) }}" target="_blank" class="text-sm font-bold text-gray-950 dark:text-white hover:underline">{{ $user->display_name }}</a>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 align-top w-full min-w-48">
                                    <div class="flex flex-col items-center">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                            {{ Permissions::toString($user->Permissions) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="fi-ta-cell px-4 py-3 align-top w-full min-w-80">
                                    <div class="flex flex-col items-center">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                            Last Login: {{ $user->last_activity_at }}
                                        </span>
                                    </div>
                                </td>
                            </tr>                        
                        @endforeach
                    </tbody>
                </table>
            @endif

        <div class="mt-6 flex justify-end gap-x-4">
            <x-filament::button color="gray" x-on:click="close()">OK</x-filament::button>
        </div>
    </x-filament::modal>
</div>
