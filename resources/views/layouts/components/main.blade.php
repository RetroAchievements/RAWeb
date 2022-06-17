<x-container :fluid="$fluid ?? false">
    <main class="{{ $class ?? 'mb-5' }}" data-scroll-target>
        @if(trim($sidebar ?? false))
            <div class="lg:grid grid-flow-col gap-4">
                <article class="lg:col-span-2 mb-3 {{ $sidebarPosition === 'right' ? 'order-1' : 'order-2'}}">
                    {{ $slot }}
                </article>
                <aside class="lg:col-span-1 {{ $sidebarPosition === 'right' ? 'order-2' : 'order-1'}}">
                    {{ $sidebar }}
                </aside>
            </div>
        @else
            <article>
                {{ $slot }}
            </article>
        @endif
    </main>
</x-container>
