@php
use \Illuminate\Support\Js;
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        @foreach($this->getAuditLog() as $auditLogItem)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex justify-between p-2">
                    <div class="flex items-center gap-4">
                        @if ($auditLogItem->causer)
                            <x-filament-panels::avatar.user :user="$auditLogItem->causer" />
                        @endif
                        <div class="flex flex-col text-left">
                            <span class="font-bold">{{ $auditLogItem->causer?->display_name }}</span>
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

                @php
                    /** @var \Spatie\Activitylog\Models\Activity $auditLogItem */
                    $properties = json_decode($auditLogItem->properties, true);
                    $changes = collect($properties);

                    $releaseIdentifier = data_get($properties, 'release_identifier');
                @endphp

                @if ($releaseIdentifier)
                    <div class="px-4 py-2 text-sm text-gray-600 bg-gray-50 border-b dark:text-gray-400 dark:bg-gray-800 dark:border-gray-700">
                        <strong>Release:</strong> {{ $releaseIdentifier }}
                    </div>
                @endif
                
                @if ($changes->isNotEmpty())
                    <table class="fi-ta-table w-full overflow-hidden text-sm">
                        <thead>
                            <tr>
                                <th class="fi-ta-header-cell">
                                    @lang('filament.audit-log.table.field')
                                </th>
                                <th class="fi-ta-header-cell">
                                    @lang('filament.audit-log.table.old')
                                </th>
                                <th class="fi-ta-header-cell">
                                </th>
                                <th class="fi-ta-header-cell">
                                    @lang('filament.audit-log.table.new')
                                </th>
                            </tr>
                        </thead>
                        <tbody>

                        @foreach (data_get($changes, 'attributes', []) as $field => $change)
                            @php
                                $oldValue = data_get($changes, "old.{$field}");
                                $newValue = data_get($changes, "attributes.{$field}");
                                $isRelationship = method_exists($this->record, $field);
                                $newRelatedModels = collect();
                                $oldRelatedModels = collect();
                                if ($isRelationship) {
                                    $oldRelatedModels = $this->record->{$field}()->getRelated()
                                        ->whereIn('id', collect($oldValue)->pluck('id')->filter())
                                        ->get();
                                    $newRelatedModels = $this->record->{$field}()->getRelated()
                                        ->whereIn('id', collect($newValue)->pluck('id')->filter())
                                        ->get();
                                }
                            @endphp

                            <tr @class(['fi-ta-row', 'fi-striped' => $loop->even])>
                                <td class="fi-ta-cell px-4 py-2 align-top sm:first-of-type:ps-6 sm:last-of-type:pe-6" width="15%">
                                    {{ $this->getFieldLabel($field) }}
                                </td>

                                <td class="fi-ta-cell px-4 py-2 align-top break-all !whitespace-normal" width="40%">
                                    @if ($oldRelatedModels->isNotEmpty() && isset($oldRelatedModels->first()->name))
                                        @foreach ($oldRelatedModels as $relatedModel)
                                            <div class="inline-block">
                                                <x-filament::badge>
                                                    {{ $relatedModel->name }}
                                                </x-filament::badge>
                                                {{ collect($oldValue)->where('id', $relatedModel->name)->get('attributes') }}
                                            </div>
                                        @endforeach
                                    @elseif ($oldValue && $this->getIsImageField($field))
                                        <img src="{{ $oldValue }}" alt="Old Image" class="max-w-full h-auto"/>
                                    @elseif (is_array($oldValue))
                                        <pre class="text-xs dark:text-neutral-200">{{ json_encode($oldValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        {{ $oldValue }}
                                    @endif
                                </td>

                                <td class="fi-ta-cell px-4 py-2 align-top text-center break-all !whitespace-normal" width="5%">
                                    <x-fas-arrow-right class="h-4 inline" />
                                </td>

                                <td class="fi-ta-cell px-4 py-2 align-top break-all !whitespace-normal" width="40%">
                                    @if ($newRelatedModels->isNotEmpty() && isset($newRelatedModels->first()->name))
                                        @foreach ($newRelatedModels as $relatedModel)
                                            <div class="inline-block">
                                                <x-filament::badge>
                                                    {{ $relatedModel->name }}
                                                </x-filament::badge>
                                                {{ collect($newValue)->where('id', $relatedModel->name)->get('attributes') }}
                                            </div>
                                        @endforeach
                                    @elseif ($this->getIsImageField($field))
                                        <img src="{{ $newValue }}" alt="New Image" class="max-w-full h-auto"/>
                                    @elseif (is_array($newValue))
                                        <pre class="text-xs dark:text-neutral-200">{{ json_encode($newValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @else
                                        {{ $newValue }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach

        <x-filament::pagination
            :page-options="$this->getTableRecordsPerPageSelectOptions()"
            :paginator="$this->getAuditLog()"
            class="px-3 py-3 sm:px-6"
        />
    </div>
</x-filament-panels::page>
