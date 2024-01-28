@props([
    'user' => filament()->auth()->user(),
])

<x-filament::avatar
    :circular="false"
    :src="filament()->getUserAvatarUrl($user)"
    :attributes="
        \Filament\Support\prepare_inherited_attributes($attributes)
            ->class(['fi-user-avatar'])
    "
/>
