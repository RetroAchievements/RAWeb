@auth
    @can('manage', App\Models\Ticket::class)
        <x-ticket-notifications-icon :class="$class ?? ''" />
    @endcan
    <x-message-notifications-icon :class="$class ?? ''" />
@endauth
