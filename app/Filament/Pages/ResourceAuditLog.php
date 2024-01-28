<?php

namespace App\Filament\Pages;

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

        return $this->paginateTableQuery(
            $this->record->auditLog()->with('causer')->latest()->getQuery()
        );
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

    protected function getEventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'deleted' => 'danger',
            'pivotAttached' => 'info',
            'pivotDetached' => 'warning',
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
