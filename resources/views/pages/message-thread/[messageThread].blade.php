<?php

use App\Community\Services\MessageThreadService;
use App\Models\MessageThread;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,messageThread']); // TODO add 'verified' middleware
name('message-thread.show');

render(function (View $view, MessageThread $messageThread, MessageThreadService $pageService) {
    $user = Auth::user();
    $currentPage = (int) (request()->input('page.number') ?? 1);

    return $view->with($pageService->buildForMessageThreadViewData($user, $messageThread, $currentPage));
});

?>

<x-app-layout
    :pageTitle="$messageThread->title"
    :pageDescription="$pageDescription"
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

    <x-section>
        <div class="mt-2">
            @if (!$canReply)
                <i>Cannot reply to deleted user.</i>
            @else
                <x-base.form action="{{ route('message.store') }}" validate>
                    <div class="flex flex-col gap-y-3">
                        <input type="hidden" name="thread_id" value="{{ $messageThread->id }}"/>
                        <x-base.form.textarea
                            :isLabelVisible="false"
                            id="input_compose"
                            name="body"
                            label="{{ __res('message', 1) }}"
                            placeholder="Enter your message here..."
                            requiredSilent
                            richText
                        >
                            <x-slot name="formActions">
                                <x-base.form-actions />
                            </x-slot>
                        </x-base.form.textarea>
                    </div>
                </x-base.form>
            @endif
        </div>

        <div id="post-preview-input_compose"></div>
    </x-section>

    <div class="w-full flex justify-end mt-2">
        <x-paginator :totalPages="$totalPages" :currentPage="$currentPage"/>
    </div>
</x-app-layout>
