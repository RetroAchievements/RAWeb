@php
    $record = $getRecord();
    $recordId = $record->id;

    $livewireComponent = $getLivewire();
    $isEditing = $livewireComponent->isEditingDisplayOrders ?? false;
@endphp

@if ($isEditing)
    <x-filament::input.wrapper class="w-24">
        <x-filament::input
            type="number"
            wire:model.blur="pendingDisplayOrders.{{ $recordId }}"
            min="0"
            max="10000"
        />
    </x-filament::input.wrapper>
@else
    <span>{{ $record->order_column ?? $record->DisplayOrder }}</span>
@endif
