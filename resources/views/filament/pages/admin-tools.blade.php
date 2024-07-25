<x-filament-panels::page>
    <x-filament::grid :xl="2" class="gap-6">
        <x-filament::section>
            <div class="flex flex-col gap-y-4">
                <x-filament::section.heading>
                    Get Game Achievement IDs
                </x-filament::section.heading>

                <livewire:administrative-tools.get-game-achievement-ids />
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex flex-col gap-y-4">
                <x-filament::section.heading>
                    Get Users Who Have Unlocked Achievement ID
                </x-filament::section.heading>

                <livewire:administrative-tools.get-users-who-have-unlocked-achievement-id />
            </div>
        </x-filament::section>
    </x-filament::grid>
</x-filament-panels::page>
