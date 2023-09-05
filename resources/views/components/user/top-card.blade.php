<?php

use App\Community\Enums\TicketFilters;
use App\Community\Enums\TicketState;
use App\Site\Enums\Permissions;
use App\Site\Models\User;

/** @var ?User $user */
$user = request()->user();

// TODO: Migrate all of this out of the component file.
$mailboxIconSrc = null;
if ($user) {
    $mailboxIconSrc = $user->UnreadMessageCount
        ? asset('assets/images/icon/mail-unread.png')
        : asset('assets/images/icon/mail.png');

    $ticketLinks = collect();
    if ($user->Permissions >= Permissions::JuniorDeveloper) {
        $openTicketsData = countOpenTicketsByDev($user->User);
        $ticketLinks->push([
            'link' => '/ticketmanager.php?u=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateRequest),
            'count' => $openTicketsData[TicketState::Open],
            'class' => 'text-danger',
            'title' => 'Tickets for you to resolve',
        ]);
        $ticketLinks->push([
            'link' => '/ticketmanager.php?u=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateOpen),
            'count' => $openTicketsData[TicketState::Request],
            'title' => 'Tickets pending feedback',
        ]);
    }

    $ticketLinks->push([
        'link' => '/ticketmanager.php?p=' . $user->User . '&t=' . (TicketFilters::Default & ~TicketFilters::StateOpen),
        'count' => countRequestTicketsByUser($user->User),
        'title' => 'Tickets awaiting your feedback',
    ]);

    $ticketLinks = $ticketLinks->filter(fn ($ticketLink) => $ticketLink['count'] > 0);
}
?>
<div class="bg-embedded rounded lg:my-4 p-[1.125rem]">
    @guest
        <form class="flex gap-[1.125rem]" action="/request/auth/login.php" method="post">
            @csrf
            <div class="flex flex-col grow gap-2">
                <label class="sr-only" for="username-input">Username</label>
                <input id="username-input" class="w-full" type="text" placeholder="Username" name="u">

                <label class="sr-only" for="password-input">Password</label>
                <input id="password-input" class="w-full" type="password" placeholder="Password" name="p">
            </div>

            <div class="flex flex-col items-center gap-2">
                <div class="h-7 flex items-center justify-center gap-x-2">
                    <button class="btn flex items-center justify-center p-2">Log In</button>
                    <a class="btn btn-link p-2" href="/createaccount.php">Register</a>
                </div>

                <a class="btn btn-link p-2 text-xs" href="/resetPassword.php">Forgot password?</a>
            </div>
        </form>
    @endguest
    @auth
        <div class="flex justify-between items-start gap-2">
            <div class="grow flex flex-col items-end">
                <div>
                    <strong><a href="{{ $user->canonicalUrl }}">{{ $user->User }}</a></strong>
                </div>
                <div class="flex gap-5 items-center">
                    <a href="/inbox.php" class="flex items-center gap-x-1" title="Unread messages: {{ $user->UnreadMessageCount }}">
                        <img id="mailboxicon"  alt="Mailbox Icon" src="{{ $mailboxIconSrc }}">
                        <span id="mailboxcount">{{ $user->UnreadMessageCount }}</span>
                    </a>
                    @if ($ticketLinks->isNotEmpty())
                        <div class="flex gap-x-1 items-center">
                            Tickets
                            @foreach($ticketLinks as $ticketLink)
                                <a
                                    class="{{ $ticketLink['class'] ?? '' }}"
                                    href="{{ $ticketLink['link'] }}"
                                    title="{{ $ticketLink['title'] }}"
                                >
                                    {{ $ticketLink['count'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if($user->Permissions >= Permissions::JuniorDeveloper)
                    @php
                        // Display claim expiring message if necessary
                        $expiringClaims = getExpiringClaim($user->User);
                    @endphp
                    @if($expiringClaims['Expired'] > 0)
                        <div>
                            <a href="/expiringclaims.php?u={{ $user->User }}">
                                <span class="text-danger">Claim Expired</span>
                            </a>
                        </div>
                    @elseif($expiringClaims['Expiring'] > 0)
                        <div>
                            <a href="/expiringclaims.php?u={{ $user->User }}">
                                <span class="text-danger">Claim Expiring Soon</span>
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endauth
</div>
