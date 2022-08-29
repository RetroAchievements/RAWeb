<?php

use App\Legacy\Models\User;
use RA\Permissions;
use RA\TicketFilters;
use RA\TicketState;

/** @var ?User $user */
$user = request()->user();
?>
<div class="bg-embedded rounded lg:w-[340px] lg:my-4 px-5 py-3">
    @guest
        <form class="flex justify-center items-end gap-2" action="/request/auth/login.php" method="post">
            @csrf
            <div class="flex flex-col gap-1">
                <input class="w-full" type="text" placeholder="Username" name="u">
                <input class="w-full" type="password" placeholder="Password" name="p">
                <a href="/resetPassword.php">Forgot password?</a>
            </div>
            <div class="flex flex-col gap-1 flex-1">
                <input type="submit" value="Login" name="submit">
                <a href="/createaccount.php">Register</a>
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
                    @php
                        $ticketLinks = collect();
                        if ($user->Permissions >= Permissions::Developer) {
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
                            'link' => '/ticketmanager.php?u=' . $user->User . '&p=' . (TicketFilters::Default & ~TicketFilters::StateOpen),
                            'count' => countRequestTicketsByUser($user->User),
                            'title' => 'Tickets awaiting your feedback',
                        ]);
                        $ticketLinks = $ticketLinks->filter(fn ($ticketLink) => $ticketLink['count'] > 0);
                    @endphp
                    @if ($ticketLinks->isNotEmpty())
                        <div class="flex gap-2">
                            Tickets
                            @foreach($ticketLinks as $ticketLink)
                                <a class="{{ $ticketLink['class'] ?? '' }}" href="{{ $ticketLink['link'] }}">
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
