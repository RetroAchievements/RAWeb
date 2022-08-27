<?php

use App\Legacy\Models\User;

/** @var User $user */
$user = request()->user();
?>

@if($settings->get('system.alert'))
    <div class="alert alert-danger mb-0 p-2">
        <div class="container">
            <x-fas-exclamation-triangle/>
            <b>{{ $settings->get('system.alert') }}</b>
        </div>
    </div>
@endif

{{-- TODO verification message --}}
{{--@auth
    @if(!auth()->user()->email_verified_at && !request()->routeIs('verification.notice'))
        <div class="alert alert-warning mb-0 p-2">
            <div class="container">
                <x-fas-exclamation-triangle />
                Your email address has not been confirmed yet. Please check your inbox or spam folders, or click
                <a href="{{ route('verification.notice') }}">here</a> to resend your activation email.
            </div>
        </div>
    @endif
@endauth--}}
@if ($user && $user->Permissions === RA\Permissions::Unregistered)
    <div class="container">
        <div class="bg-orange-500 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-exclamation-triangle/>
            Your email address has not been confirmed yet. Please check your inbox or spam folders, or click
            <form class="inline" action="/request/auth/send-verification-email.php?u={{ $user->User }}" method="post">
                @csrf
                <button class="btn btn-link bg-transparent p-0 text-white underline">here</button> to resend your activation email.
            </form>
        </div>
    </div>
@endif


{{-- TODO toasts --}}
<div class="sticky top-14 z-50 container">
    <div id="status" class="hidden absolute w-full text-gray-200 px-5 py-2 rounded-sm"></div>
</div>
{{--<div aria-live="polite" aria-atomic="true"
     class="toast-container flex flex-column justify-end lg:justify-center items-end m-3">
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
    <div class="container">
        <div class="bg-blue-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-info-circle/>
            {{ session('message') }}
        </div>
    </div>
@endif

@if(session('success'))
    <div class="container">
        <div class="bg-green-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-check/>
            {{ session('success') }}
        </div>
    </div>
@endif

@if($error = session('error'))
    <div class="container">
        <div class="bg-red-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
            <x-fas-exclamation-triangle/>
            {{ session('error') }}
        </div>
    </div>
@endif

@if(($errors ?? null) && $errors->count())
    <div class="container">
        @foreach($errors->all() as $error)
            <div class="bg-red-600 my-2 text-gray-200 px-5 py-2 rounded-sm">
                <x-fas-exclamation-triangle/>
                {{ $error }}
            </div>
        @endforeach
    </div>
@endif
