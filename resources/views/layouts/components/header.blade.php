<header class="{{ $class && !Route::is('passport.*') ?? 'sm:mb-5' }}">
    {{ $slot }}
</header>
