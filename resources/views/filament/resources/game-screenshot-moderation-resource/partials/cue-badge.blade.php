@php
    $toneClasses = match ($tone) {
        'danger' => 'border-danger-500/35 bg-danger-500/10 text-danger-700 dark:text-danger-300',
        'warning' => 'border-warning-500/35 bg-warning-500/10 text-warning-700 dark:text-warning-300',
        'success' => 'border-success-500/35 bg-success-500/10 text-success-700 dark:text-success-300',
        default => 'border-gray-500/25 bg-gray-500/10 text-gray-600 dark:text-gray-300',
    };
@endphp

<span
    @if ($title) title="{{ $title }}" @endif
    class="mt-1 inline-flex items-center gap-1 rounded-full border px-1.5 py-0.5 text-[0.7rem] font-semibold leading-4 {{ $toneClasses }}"
>
    {!! svg($icon, 'size-3 shrink-0', ['aria-hidden' => 'true', 'data-cue-icon' => $icon])->toHtml() !!}
    <span>{{ $label }}</span>
</span>
