<?php

use App\Site\Models\User;

/** @var User $user */
$user = request()->user();
?>
@if($settings->get('system.alert'))
    <div class="alert alert-danger mb-0 p-2">
        <x-container>
            <x-fas-exclamation-triangle/>
            <b>{{ $settings->get('system.alert') }}</b>
        </x-container>
    </div>
@endif
@auth
    {{-- TODO verification message
    @if(!auth()->user()->email_verified_at && !request()->routeIs('verification.notice'))
        <div class="alert alert-warning mb-0 p-2">
            <x-container>
                <x-fas-exclamation-triangle />
                Your email address has not been confirmed yet. Please check your inbox or spam folders, or click
                <a href="{{ route('verification.notice') }}">here</a> to resend your activation email.
            </x-container>
        </div>
    @endif
    --}}
    @if (!$user->hasVerifiedEmail())
        <x-container>
            <div class="bg-orange-500 my-2 text-gray-200 px-5 py-2 rounded-sm">
                <x-fas-exclamation-triangle/>
                Your email address has not been confirmed yet. Please check your inbox or spam folders, or click
                <form class="inline" action="/request/auth/send-verification-email.php" method="post">
                    @csrf
                    <button class="btn btn-link bg-transparent p-0 text-white underline">here</button>
                    to resend your activation email.
                </form>
            </div>
        </x-container>
    @endif
    @if ($user->DeleteRequested)
        <x-container>
            <div class="bg-orange-500 my-2 text-gray-200 px-5 py-2 rounded-sm">
                <x-fas-exclamation-triangle/>
                Your account is marked to be deleted on {{ getDeleteDate($user->DeleteRequested) }}.
            </div>
        </x-container>
    @endif
@endauth
{{-- TODO toasts --}}
<div class="sticky top-14 z-10 container">
    <div id="status" class="hidden absolute w-full text-gray-200 px-5 py-2 rounded-sm"></div>
</div>
{{--<div aria-live="polite" aria-atomic="true"
     class="toast-container flex flex-col justify-end lg:justify-center items-end m-3">
    @if(session('success'))
        <x-toast status="success">
            <x-slot name="icon">
                <x-fas-check />
            </x-slot>
            {{ session('success') }}
        </x-toast>
    @endif
    @if(session('error'))
        <x-toast status="danger">
            <x-slot name="icon">
                <x-fas-exclamation-triangle />
            </x-slot>
            {{ session('error') }}
        </x-toast>
    @endif
    @if($errors->count())
        <x-toast status="warning">
            <x-slot name="icon">
                <x-fas-exclamation-triangle />
            </x-slot>
            @foreach($errors->all() as $error)
                <p class="mb-1">
                    {{ $error }}
                </p>
            @endforeach
        </x-toast>
    @endif
</div>--}}
@if(session('message'))
    <x-container>
        <div class="bg-blue-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-info-circle/>
            {{ session('message') }}
        </div>
    </x-container>
@endif
@if(session('success'))
    <x-container>
        <div class="bg-green-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-check/>
            {{ session('success') }}
        </div>
    </x-container>
@endif
@if($error = session('error'))
    <x-container>
        <div class="bg-red-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-exclamation-triangle/>
            {{ session('error') }}
        </div>
    </x-container>
@endif
@if(($errors ?? null) && $errors->count())
    {{-- TODO differentiate between validation errors and custom errors --}}
    <x-container>
        @foreach($errors->all() as $error)
            <div class="bg-red-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
                <x-fas-exclamation-triangle/>
                {{ $error }}
            </div>
        @endforeach
    </x-container>
@endif
