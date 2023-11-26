@props([
    'thread' => null,
    'messages' => [],
    'participants' => [],
    'currentPage' => 1,
    'totalPages' => 1,
    'canReply' => true,
])

<?php

use App\Site\Enums\UserPreference;
use App\Site\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;

$user = request()->user();
$isShowAbsoluteDatesPreferenceSet = BitSet(request()->user()->websitePrefs, UserPreference::Forum_ShowAbsoluteDates);
$monthAgo = Carbon::now()->subMonth(1);

$participantModels = [];
if (empty($participants)) {
    foreach ($messages as $message) {
        if (!array_key_exists($message->author_id, $participants)) {
            $participantModel = User::withTrashed()->firstWhere('id', $message->author_id);
            if ($participantModel) {
                $participantModels[$message->author_id] = $participantModel;
                $participants[$participantModel->ID] = $participantModel->User;
            }
        }
    }
} else {
    foreach ($participants as $id => $participant) {
        $participantModel = User::withTrashed()->firstWhere('id', $id);
        if ($participantModel) {
            $participantModels[$id] = $participantModel;
        }
    }
}
$pageDescription = "Conversation between " . implode(' and ', $participants);

?>

<x-app-layout
    pageTitle="{{ $thread->title }}"
    pageDescription="{{ $pageDescription }}"
>
    <x-user.breadcrumbs targetUsername="{{ $user->User }}"
        parentPage="Messages"
        parentPageUrl="{{ route('message.list') }}"
        currentPage="{!! $thread->title !!}" />

    <div class="mt-3 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">{!! $thread->title !!}</h1>
    </div>

    <div class="w-full flex my-2">
        <div class="mr-6">
            <form action='/request/message/delete.php' method='post'>
            {{ csrf_field() }}
            <input type="hidden" name="thread_id" value="{{ $thread->id }}" />
            <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this message?')">Delete</button>
            </form>
        </div>
        <div class="w-full flex justify-end">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    </div>

    <div class="flex flex-col gap-y-1">
        @foreach ($messages as $message)
            <div class="embedded mb-2">
                <div class="flex justify-between items-center">
                    <div>
                    {!! userAvatar($participants[$message->author_id], iconSize: 24) !!}
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
                <hr class="w-full border-embed-highlight my-2" />

                <p class="comment text-overflow-wrap">{!! Shortcode::render($message->body) !!}</p>
            </div>
        @endforeach
    </div>

    @if (!$canReply)
        <div class="mt-2"><i>Cannot reply to deleted user.</i></div>
    @else
        <form action='/request/message/create.php' method='post' x-data='{ isValid: true }'>
            {{ csrf_field() }}
            <input type='hidden' name='thread_id' value='{{ $thread->id }}' />

            <x-input.shortcode-textarea
                name='body'
                watermark='Enter your message here...'
            />
        </form>
    @endif

    <div class="w-full flex justify-end mt-2">
        <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
    </div>

</x-app-layout>
