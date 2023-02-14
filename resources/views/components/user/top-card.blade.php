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
        <form class="grid grid-cols-2 gap-2" action="/request/auth/login.php" method="post">
            @csrf
            <div class="flex gap-2 flex-col w-full">
                <label class="sr-only" for="username-input">Username</label>
                <input class="w-full" type="text" placeholder="Username" id="username-input" name="u">

                <label class="sr-only" for="password-input">Password</label>
                <input class="w-full" type="password" placeholder="Password" id="password-input" name="p">
            </div>
            
            <div class="flex flex-col items-center gap-2 w-full h-full">
                <div class="h-7 flex items-center justify-center gap-x-2">
                    <button class="flex items-center justify-center p-2" type="submit" name="submit">Log In</button>
                    <a class="btn btn-link p-2" href="/createaccount.php">Register</a>
                </div>

                <a class="btn btn-link p-2 text-xs" href="/resetPassword.php">Forgot password?</a>
            </div>
        </form>
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
