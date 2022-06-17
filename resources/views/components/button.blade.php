<button class="btn {{ $class ?? null }}" type="{{ $type ?? 'button' }}">
    {{--x-on:click.prevent="$dispatch('alpine-event', { foo: 'bar' })"--}}
    {{ $slot ?? 'Submit' }}
</button>
