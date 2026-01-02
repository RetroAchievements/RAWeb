<?php

namespace App\Filament\Pages;

use App\Models\Achievement;
use BackedEnum;
use Closure;
use Filament\Forms;
use Filament\Pages;
use Filament\Schemas;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

abstract class ResourceAuditLog extends \Filament\Resources\Pages\Page implements Forms\Contracts\HasForms
{
    use Pages\Concerns\InteractsWithFormActions;
    use \Filament\Resources\Pages\Concerns\InteractsWithRecord;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'fas-clock-rotate-left';

    protected string $view = 'filament.pages.audit-log';

    public int $tableRecordsPerPage = 10;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->tableRecordsPerPage = $this->getTableRecordsPerPageSelectOptions()[0];
    }

    public function getTitle(): string|Htmlable
    {
        $resourceClass = static::getResource();
        $recordTitle = $resourceClass::getRecordTitle($this->record);

        return "{$recordTitle} - Audit Log";
    }

    public function getBreadcrumb(): string
    {
        return 'Audit Log';
    }

    /**
     * @return LengthAwarePaginator<int, Model>
     */
    public function getAuditLog(): LengthAwarePaginator
    {
        if (!method_exists($this->record, 'auditLog')) {
            return new LengthAwarePaginator([], 0, 1);
        }

        $query = $this->record->auditLog()->with('causer')->latest();
        $paginator = $query->paginate($this->tableRecordsPerPage);

        // Map raw values to human-readable values.
        // eg: 3 -> "Published"
        foreach ($paginator->items() as $log) {
            $properties = json_decode($log->properties, true);

            if (isset($properties['attributes'])) {
                $properties['attributes'] = $this->transformFieldValues($properties['attributes']);
            }

            if (isset($properties['old'])) {
                $properties['old'] = $this->transformFieldValues($properties['old']);
            }

            $log->properties = $properties;
        }

        return $paginator;
    }

    public function getFieldLabel(string $name): string
    {
        return $this->createFieldLabelMap()[$name] ?? $name;
    }

    /**
     * @return Collection<string, mixed>
     */
    protected function createFieldLabelMap(): Collection
    {
        $form = static::getResource()::form(new Schemas\Schema($this));

        return collect($form->getFlatFields())
            ->mapWithKeys(fn (Forms\Components\Field $field) => [
                $field->getName() => $field->getLabel(),
            ]);
    }

    /**
     * @return Collection<string, Closure(mixed): string>
     */
    protected function createFieldValueMap(): Collection
    {
        return collect([
            'is_promoted' => fn (mixed $value): string => $value ? __('Promoted') : __('Unpromoted'),

            // Support legacy audit log records that used the Flags column.
            'Flags' => fn (mixed $value): string => $value === Achievement::FLAG_PROMOTED ? __('Promoted') : __('Unpromoted'),
        ]);
    }

    protected function transformFieldValues(array $values): array
    {
        $fieldValueMap = $this->createFieldValueMap();

        foreach ($values as $key => $value) {
            if ($fieldValueMap->has($key) && is_callable($fieldValueMap->get($key))) {
                $values[$key] = $fieldValueMap->get($key)($value);
            }

            if ($this->getIsImageField($key) && is_string($value)) {
                $values[$key] = $this->getImageUrl($key, $value);
            }
        }

        return $values;
    }

    protected function getIsImageField(string $fieldName): bool
    {
        return in_array($fieldName, [
            'image_name',
            'image_asset_path',

            // New column names.
            'image_icon_asset_path',
            'image_box_art_asset_path',
            'image_title_asset_path',
            'image_ingame_asset_path',

            // Legacy column names for historical audit log entries.
            'ImageIcon',
            'ImageBoxArt',
            'ImageTitle',
            'ImageIngame',
        ]);
    }

    protected function getImageUrl(string $fieldName, string $path): string
    {
        switch ($fieldName) {
            case 'image_name':
                return media_asset("/Badge/{$path}.png");

            default:
                return media_asset($path);
        }
    }

    protected function getEventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'deleted' => 'danger',
            'linkedHash' => 'success',
            'multisetDisabled' => 'danger',
            'multisetEnabled' => 'info',
            'pivotAttached' => 'info',
            'pivotDetached' => 'warning',
            'releaseCreated' => 'success',
            'releaseDeleted' => 'danger',
            'releaseUpdated' => 'info',
            'resetAllLeaderboardEntries' => 'danger',
            'unlinkedHash' => 'danger',
            'updatedHash' => 'info',
            default => 'info',
        };
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [50];
    }
}
