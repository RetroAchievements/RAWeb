@props([
    'messageChain' => null,
    'messages' => [],
    'currentPage' => 1,
    'totalPages' => 1,
])

<?php

use App\Site\Enums\UserPreference;
use App\Site\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;

$userTo = User::firstWhere('ID', $messageChain->recipient_id);
$userFrom = User::firstWhere('ID', $messageChain->sender_id);

$isShowAbsoluteDatesPreferenceSet = BitSet(request()->user()->websitePrefs, UserPreference::Forum_ShowAbsoluteDates);
$monthAgo = Carbon::now()->subMonth(1);

?>

<script>
function deleteMessage() {
    if (confirm('Are you sure you want to delete this message?')) {
        $.post('/request/message/delete.php', {
            chain: {{ $messageChain->id }},
            user: "{{ request()->user()->User }}"
        })
    }
}
</script>

<x-app-layout
    pageTitle="{{ $messageChain->title }}"
    pageDescription="Conversation between {{ $userFrom->User }} and {{ $userTo->User }}"
>
    <x-message.breadcrumbs currentPage="{!! $messageChain->title !!}" />

    <div class="w-full flex mt-2">
        <div class="mr-6">
            <button class='btn btn-danger' onclick='deleteMessage()'>Delete</button>
        </div>
        <div class="w-full flex justify-end">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    </div>

    <div class="flex flex-col gap-y-1">
        @foreach ($messages as $message)
            <div class="embedded">
                <div class="flex justify-between items-center">
                    <div>
                    {!! userAvatar($message->author_id == $userTo->ID ? $userTo : $userFrom, iconSize: 16) !!}
                    <?php $humanDate = $message->created_at->format('F j Y, g:ia'); ?>
                    @if ($isShowAbsoluteDatesPreferenceSet)
                        <span class='smalldate'>{{ $humanDate }}</span>
                    @elseif ($message->created_at < $monthAgo)
                        <span class='smalldate'>{{ $humanDate }}</span>
                    @else
                        <span class='smalldate cursor-help' title='{{ $humanDate }}'>{{ $message->created_at->diffForHumans() }}</span>
                    @endif
                    </div>
                </div>

                <p class="comment text-overflow-wrap">{!! Shortcode::render($message->body) !!}</p>
            </div>
        @endforeach
    </div>

    <div class="w-full flex justify-end mt-2">
        <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
    </div>

</x-app-layout>
