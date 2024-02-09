<?php

use App\Enums\UserPreference;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Carbon;
use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:view,messageThread']);
name('message-thread.show');

?>

@props([
    'messages' => [],
    'participants' => [],
    'currentPage' => 1,
    'totalPages' => 1,
    'canReply' => true,
])

@php
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
@endphp

<x-app-layout
    pageTitle="{{ $messageThread->title }}"
    pageDescription="{{ $pageDescription }}"
>
    <x-message.breadcrumbs currentPage="{!! $messageThread->title !!}" />

    <div class="mt-3 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">{!! $messageThread->title !!}</h1>
    </div>

    <div class="w-full flex my-2">
        <div class="mr-6">
            <form action="{{ route('message-thread.destroy', $messageThread->id) }}" method="post">
                {{ method_field('DELETE') }}
                {{ csrf_field() }}
                <button class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this message thread?')">Delete</button>
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
                        @php
                        $humanDate = $message->created_at->format('F j Y, g:ia');
                        @endphp
                        @if ($isShowAbsoluteDatesPreferenceSet || $message->created_at < $monthAgo)
                            <span class="smalldate">{{ $humanDate }}</span>
                        @else
                            <span class="smalldate cursor-help" title="{{ $humanDate }}">{{ $message->created_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
                <hr class="w-full border-embed-highlight my-2"/>

                <p class="comment text-overflow-wrap">{!! Shortcode::render($message->body) !!}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-2">
        @if (!$canReply)
            <i>Cannot reply to deleted user.</i>
        @else
            <form action="{{ route('message.store') }}" method="post" x-data="{ isValid: true }">
                {{ csrf_field() }}
                <input type="hidden" name="thread_id" value="{{ $messageThread->id }}"/>

                <x-input.shortcode-textarea
                    name="body"
                    placholder="Enter your message here..."
                />
            </form>
        @endif
    </div>

    <div class="w-full flex justify-end mt-2">
        <x-paginator :totalPages="$totalPages" :currentPage="$currentPage"/>
    </div>

</x-app-layout>
