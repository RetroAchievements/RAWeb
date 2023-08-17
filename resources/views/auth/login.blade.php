<x-prompt-layout
    :page-title="__('Login')"
>
    <x-slot name="header">
        <h1 class="mb-0">{{ __('Sign in to RetroAchievements') }}</h1>
    </x-slot>

    <x-form :action="route('login')">
        <x-input.text attribute="User" required />
        <x-input.password attribute="password" required />
        <x-input.checkbox attribute="remember" :label="__('Remember Me')" />
        <x-button type="submit" class="w-full text-center py-2 mb-4">{{ __('Sign in') }}</x-button>
        <div class="flex flex-col justify-between">
            <a class="btn btn-link" href="{{ url('resetPassword.php') }}">
                {{ __('Forgot your password?') }}
            </a>
            <a class="btn btn-link" href="{{ url('createaccount.php') }}">
                {{ __('Sign up') }}
            </a>
        </div>
    </x-form>
</x-prompt-layout>
