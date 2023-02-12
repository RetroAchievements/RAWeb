<?php

use LegacyApp\Community\Enums\TicketFilters;
use LegacyApp\Community\Enums\TicketState;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;

/** @var ?User $user */
$user = request()->user();
?>
<div class="bg-embedded rounded lg:w-[340px] lg:my-4 px-5 py-3">
    @guest
        <form class="mb-2" action="/request/auth/login.php" method="post">
            @csrf
            <div class="grid lg:flex gap-2 mb-2">
                <div>
                    <label for="username-input" class="sr-only">Username</label>
                    <input class="w-full p-2" type="text" placeholder="Username" id="username-input" name="u">
                </div>
                <div>
                    <label for="password-input" class="sr-only">Password</label>
                    <input class="w-full p-2" type="password" placeholder="Password" id="password-input" name="p">
                </div>

                <button type="submit" name="submit" class="flex items-center justify-center p-2">Log In</button>
            </div>
        </form>

        <div class="grid grid-cols-2 lg:flex lg:justify-between text-center">
            <a href="/createaccount.php">Register</a>
            <a href="/resetPassword.php">Forgot Password?</a>
        </div>
    @endguest
    @auth
        <div class="flex justify-between items-start gap-2">
            <a class="order-2" href="{{ $user->canonicalUrl }}">
                <img class="userpic" src="{{ $user->avatarUrl }}" alt="Profile Picture" width="64" height="64">
            </a>
            <div class="grow flex flex-col justify-between">
                <div>
                    <strong><a href="{{ $user->canonicalUrl }}">{{ $user->User }}</a></strong>
                </div>
                <div class="flex gap-2">
                    @if ($user->RAPoints)
                        <span>({{ $user->RAPoints }})</span>
                        <span class="TrueRatio">({{ $user->TrueRAPoints }})</span>
                    @endif
                    @if ($user->RASoftcorePoints)
                        <span class="softcore">({{ $user->RASoftcorePoints }} softcore)</span>
                    @endif
                </div>
                <div class="flex gap-5 items-center">
                    <a href="/inbox.php">
                        <img class="mr-1" id="mailboxicon" alt="Mailbox Icon" src="{{ $user->UnreadMessageCount ? asset('assets/images/icon/mail-unread.png') : asset('assets/images/icon/mail.png') }}">
                        <span id="mailboxcount">{{ $user->UnreadMessageCount }}</span>
                    </a>
                    <?php
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
                    ?>
                    @if ($ticketLinks->isNotEmpty())
                        <div class="flex gap-2">
                            Tickets
                            @foreach($ticketLinks as $ticketLink)
                                <a class="{{ $ticketLink['class'] ?? '' }}" href="{{ $ticketLink['link'] }}" title="{{ $ticketLink['title'] }}">
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
