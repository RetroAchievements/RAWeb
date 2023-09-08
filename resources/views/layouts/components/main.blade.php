<x-container :fluid="$fluid ?? false">
    <main class="{{ $class ?? 'mb-5' }} {{ trim($sidebar ?? false) ? 'with-sidebar' : '' }}" data-scroll-target>
        @if(trim($sidebar ?? false))
            @if($withoutWrappers)
                {{ $slot }}
                {{ $sidebar }}
            @else
                <article class="{{ $sidebarPosition === 'right' ? 'order-2' : 'order-1' }}">
                    {{ $slot }}
                </article>
                <aside class="{{ $sidebarPosition === 'right' ? 'order-2' : 'order-1' }}">
                    {{ $sidebar }}
                </aside>
            @endif
        @else
            @if($withoutWrappers)
                {{ $slot }}
            @else
                <article>
                    {{ $slot }}
                </article>
            @endif
        @endif
    </main>
</x-container>
