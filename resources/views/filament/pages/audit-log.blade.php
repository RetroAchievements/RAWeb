@php
use \Illuminate\Support\Js;
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($this->getAuditLog() as $auditLogItem)
            <x-filament-tables::container>
                <div class="p-2">
                    <div class="flex justify-between">
                        <div class="flex items-center gap-4">
                            @if ($auditLogItem->causer)
                                <x-filament-panels::avatar.user :user="$auditLogItem->causer" />
                            @endif
                            <div class="flex flex-col text-left">
                                <span class="font-bold">{{ $auditLogItem->causer?->User }}</span>
                                <span class="text-xs text-gray-500">
                                    <div class="inline-block">
                                        <x-filament::badge
                                            :color="$this->getEventColor($auditLogItem->event)"
                                        >
                                            @lang('filament.audit-log.events.' . $auditLogItem->event)
                                        </x-filament::badge>
                                    </div>
                                    {{ $auditLogItem->created_at->format('Y-m-d H:i:s') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                /** @var \Spatie\Activitylog\Models\Activity $auditLogItem */
                $changes = $auditLogItem->getChangesAttribute();
                @endphp
                @if($changes->isNotEmpty())
                    <x-filament-tables::table class="w-full overflow-hidden text-sm">
                        <x-slot:header>
                            <x-filament-tables::header-cell>
                                @lang('filament.audit-log.table.field')
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                @lang('filament.audit-log.table.old')
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                            </x-filament-tables::header-cell>
                            <x-filament-tables::header-cell>
                                @lang('filament.audit-log.table.new')
                            </x-filament-tables::header-cell>
                        </x-slot:header>
                        @foreach(data_get($changes, 'attributes', []) as $field => $change)
                            @php
                            $oldValue = data_get($changes, "old.{$field}");
                            $newValue = data_get($changes, "attributes.{$field}");
                            $isRelationship = method_exists($this->record, $field);
                            $newRelatedModels = collect();
                            $oldRelatedModels = collect();
                            if($isRelationship) {
                                $oldRelatedModels = $this->record->{$field}()->getRelated()
                                    ->whereIn('id', collect($oldValue)->pluck('id')->filter())
                                    ->get();
                                $newRelatedModels = $this->record->{$field}()->getRelated()
                                    ->whereIn('id', collect($newValue)->pluck('id')->filter())
                                    ->get();
                            }
                            @endphp
                            <x-filament-tables::row @class(['bg-gray-100/30' => $loop->even])>
                                <x-filament-tables::cell width="15%" class="px-4 py-2 align-top sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    {{ $this->getFieldLabel($field) }}
                                </x-filament-tables::cell>
                                <x-filament-tables::cell width="40%" class="px-4 py-2 align-top break-all !whitespace-normal">
                                    @if($oldRelatedModels->isNotEmpty())
                                        @foreach($oldRelatedModels as $relatedModel)
                                            <div class="inline-block">
                                                <x-filament::badge>
                                                    {{ $relatedModel->name }}
                                                </x-filament::badge>
                                                {{ collect($oldValue)->where('id', $relatedModel->name)->get('attributes') }}
                                            </div>
                                        @endforeach
                                    @elseif(is_array($oldValue))
                                        <pre class="text-xs text-gray-500">{{ json_encode($oldValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        {{ $oldValue }}
                                    @endif
                                </x-filament-tables::cell>
                                <x-filament-tables::cell width="5%" class="px-4 py-2 align-top text-center break-all !whitespace-normal">
                                    <x-fas-arrow-right class="h-4 inline" />
                                </x-filament-tables::cell>
                                <x-filament-tables::cell width="40%" class="px-4 py-2 align-top break-all !whitespace-normal">
                                    @if($newRelatedModels->isNotEmpty())
                                        @foreach($newRelatedModels as $relatedModel)
                                            <div class="inline-block">
                                                <x-filament::badge>
                                                    {{ $relatedModel->name }}
                                                </x-filament::badge>
                                                {{ collect($newValue)->where('id', $relatedModel->name)->get('attributes') }}
                                            </div>
                                        @endforeach
                                    @elseif(is_array($newValue))
                                        <pre class="text-xs text-gray-500">{{ json_encode($newValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        {{ $newValue }}
                                    @endif
                                </x-filament-tables::cell>
                            </x-filament-tables::row>
                        @endforeach
                    </x-filament-tables::table>
                @endif
            </x-filament-tables::container>
        @endforeach
        <x-filament::pagination
            :page-options="$this->getTableRecordsPerPageSelectOptions()"
            :paginator="$this->getAuditLog()"
            class="px-3 py-3 sm:px-6"
        />
    </div>
</x-filament-panels::page>
