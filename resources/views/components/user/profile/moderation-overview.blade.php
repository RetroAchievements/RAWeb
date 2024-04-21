@props([
    'targetUser' => null, // User
])

<div class="mb-6">
    <div class="flex w-full items-center gap-x-1.5 mb-0.5">
        <p role="heading" aria-level="2" class="text-2xs font-bold">
            Moderation Overview
        </p>
    </div>

    <div class="w-full p-2 bg-embed flex flex-col gap-y-2 rounded">
        <div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
            @if ($targetUser->is_muted)
                <x-user.profile.stat-element
                    label="Muted until"
                    value="{{ $targetUser->muted_until->format('Y-m-d') }}"
                    :href="route('filament.admin.resources.users.edit', $targetUser)"
                />
            @endif

            @if ($targetUser->is_unranked)
                <x-user.profile.stat-element
                    label="Unranked at"
                    value="{{ $targetUser->unranked_at->format('Y-m-d') }}"
                    :href="route('filament.admin.resources.users.edit', $targetUser)"
                />
            @endif

            @if ($targetUser->is_banned)
                <x-user.profile.stat-element
                    label="Banned at"
                    value="{{ $targetUser->banned_at->format('Y-m-d') }}"
                    :href="route('filament.admin.resources.users.edit', $targetUser)"
                />
            @endif

            @if ($targetUser->DeleteRequested)
                <x-user.profile.stat-element
                    label="Delete requested at"
                    value="{{ $targetUser->DeleteRequested->format('Y-m-d') }}"
                />
            @endif
        </div>
    </div>
</div>
