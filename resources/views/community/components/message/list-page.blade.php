@props([
    'messages' => [],
    'currentPage' => 1,
    'totalPages' => 1,
    'unreadCount' => 0,
    'totalMessages' => 0,
    'mode' => 'inbox',
])

<?php

use App\Site\Enums\UserPreference;
use App\Site\Models\User;
use Illuminate\Support\Carbon;

$pageTitle = ($mode == 'outbox') ? 'Outbox' : 'Inbox';
$toFromLabel = ($mode == 'outbox') ? 'To' : 'From';

$user = request()->user();
$isShowAbsoluteDatesPreferenceSet = BitSet($user->websitePrefs, UserPreference::Forum_ShowAbsoluteDates);
$monthAgo = Carbon::now()->subMonth(1);

?>

<script>
function deleteMessage(id) {
    if (confirm('Are you sure you want to delete this message?')) {
        $.post('/request/message/delete.php', {
            chain: id,
            user: "{{ $user->User }}"
        })
    }
}
</script>

<x-app-layout
    pageTitle="{{ $pageTitle }}"
    pageDescription="Messages involving {{ $user->User }}"
>
    <x-message.breadcrumbs currentPage="{{ $pageTitle }}" />

    <div class="mt-3 w-full flex gap-x-3">
        {!! userAvatar($user->User, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $user->User }}'s {{ $pageTitle }}</h1>
    </div>

    <div class='ml-2'>
        @if ($mode == 'outbox')
            You have {{ $totalMessages }} sent messages.
        @else
            You have {{ $unreadCount }} unread of {{ $totalMessages }} total messages.
        @endif
    </div>

    <div class="w-full flex mt-2">
        <div class="mr-6">
            @if ($mode == 'outbox')
                <a href='{{ route('message.inbox') }}'><button class='btn'>Inbox</button></a>
            @else
                <a href='{{ route('message.outbox') }}'><button class='btn'>Outbox</button></a>
            @endif
        </div>
        <div class="mr-6">
            <a href='{{ route('message.new') }}'><button class='btn'>New Message</button></a>
        </div>
        <div class="w-full flex justify-end">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    </div>

    <div><table class='table-highlight mb-4'><tbody>

    <tr>
        <th style='width:20%'>{{ $toFromLabel }}</th>
        <th style='width:55%'>Title</th>
        <th style='width:10%' class='text-right'>Messages</th>
        <th style='width:15%' class='text-right'>Last Message</th>
    </tr>
    @foreach ($messages as $message)
        <?php 
            $mostRecentUpdate = Carbon::parse($message->sender_last_post_at);
            if ($message->recipient_last_post_at) {
                $mostRecentReply = Carbon::parse($message->recipient_last_post_at);
                if ($mostRecentReply > $mostRecentUpdate) {
                    $mostRecentUpdate = $mostRecentReply;
                }
            }
            $humanDate = $mostRecentUpdate->format('F j Y, g:ia');

            $otherUser = ($user->id == $message->recipient_id) ?
                User::withTrashed()->firstWhere('ID', $message->sender_id) :
                User::withTrashed()->firstWhere('ID', $message->recipient_id);

            $num_unread = 0;
            if ($message->recipient_id == $user->id && $message->recipient_num_unread > 0) {
                $num_unread = $message->recipient_num_unread;
            } elseif ($message->sender_id == $user->id && $message->sender_num_unread > 0) {
                $num_unread = $message->sender_num_unread;
            }
        ?>
        <tr>
            <td>
                @if ($num_unread > 0)
                    <b>
                @endif
                {!! userAvatar($otherUser, iconSize: 24) !!}
                @if ($num_unread > 0)
                    </b>
                @endif
            </td>

            <td>
                <a href='{{ route('message.view-chain', $message->id) }}'>
                    @if ($num_unread > 0)
                        <b>
                    @endif
                    @if ($mode == 'outbox')
                        @if ($message->recipient_id == $user->id)
                            RE:
                        @endif
                    @else
                        @if ($message->sender_id == $user->id)
                            RE:
                        @endif
                    @endif
                    {{ $message->title }}
                    @if ($num_unread > 0)
                        </b>
                    @endif
                </a>
            </td>

            <td class='text-right'>
                @if ($num_unread == 0)
                    {{ $message->num_messages }}
                @elseif ($num_unread == $message->num_messages)
                    <b>{{ $num_unread }} (unread)</b>
                @else
                    {{ $message->num_messages }} <b>({{ $num_unread }} unread)</b>
                @endif
            </td>

            <td class='text-right'>
                @if ($isShowAbsoluteDatesPreferenceSet)
                    <span class='smalldate'>{{ $humanDate }}</span>
                @elseif ($mostRecentUpdate < $monthAgo)
                    <span class='smalldate'>{{ $humanDate }}</span>
                @else
                    <span class='smalldate cursor-help' title='{{ $humanDate }}'>{{ $mostRecentUpdate->diffForHumans() }}</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody></table></div>

    <div class="w-full flex justify-end mt-2">
        <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
    </div>

</x-app-layout>
