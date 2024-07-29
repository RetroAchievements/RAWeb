<?php

namespace App\Filament\Pages;

use App\Platform\Enums\AchievementFlag;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\CanPaginateRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Features\SupportPagination\HandlesPagination;
use Livewire\WithPagination;

abstract class ResourceAuditLog extends Page implements HasForms
{
    use CanPaginateRecords;
    use HandlesPagination;
    use InteractsWithFormActions;
    use InteractsWithRecord;
    use WithPagination {
        WithPagination::resetPage as resetLivewirePage;
    }

    protected static ?string $navigationIcon = 'fas-clock-rotate-left';

    protected static string $view = 'filament.pages.audit-log';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->tableRecordsPerPage = $this->getTableRecordsPerPageSelectOptions()[0];
    }

    /**
     * @return LengthAwarePaginator<Model>
     */
    public function getAuditLog(): LengthAwarePaginator
    {
        if (!method_exists($this->record, 'auditLog')) {
            return new LengthAwarePaginator([], 0, 1);
        }

        $query = $this->record->auditLog()->with('causer')->latest()->getQuery();
        $paginator = $this->paginateTableQuery($query);

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
     * @return Collection<string, \Illuminate\Contracts\Support\Htmlable|string|null>
     */
    protected function createFieldLabelMap(): Collection
    {
        $form = static::getResource()::form(new Form($this));

        $components = new Collection($form->getComponents());
        $extracted = new Collection();

        while (($component = $components->shift()) !== null) {
            if ($component instanceof Field || $component instanceof MorphToSelect) {
                $extracted->push($component);

                continue;
            }

            $children = $component->getChildComponents();

            if (count($children) > 0) {
                $components = $components->merge($children);

                continue;
            }

            $extracted->push($component);
        }

        return $extracted
            ->filter(fn (mixed $field): bool => $field instanceof Field) // @phpstan-ignore-line
            ->mapWithKeys(fn (Field $field) => [
                $field->getName() => $field->getLabel(),
            ]);
    }

    /**
     * @return Collection<string, Closure(int): string>
     */
    protected function createFieldValueMap(): Collection
    {
        return collect([
            'Flags' => fn (int $flag): string => AchievementFlag::toString($flag),
        ]);
    }

    protected function transformFieldValues(array $values): array
    {
        $fieldValueMap = $this->createFieldValueMap();

        foreach ($values as $key => $value) {
            if ($fieldValueMap->has($key) && is_callable($fieldValueMap->get($key))) {
                $values[$key] = $fieldValueMap->get($key)($value);
            }
        }

        return $values;
    }

    protected function getEventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'deleted' => 'danger',
            'pivotAttached' => 'info',
            'pivotDetached' => 'warning',
            'resetAllLeaderboardEntries' => 'danger',
            default => 'info',
        };
    }

    protected function getIdentifiedTableQueryStringPropertyNameFor(string $property): string
    {
        return $property;
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return config('filament.default_page_options');
    }
}
