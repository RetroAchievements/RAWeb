<x-prompt-layout
    :page-title="__('Login')"
>
    <x-slot name="header">
        <h1 class="text-h4 mb-0">{{ __('Sign in to RetroAchievements') }}</h1>
    </x-slot>

    <x-form :action="route('login')">
        <x-input.text name="User" label="Username" requiredSilent />
        <x-input.password name="password" requiredSilent />
        <x-input.checkbox name="remember" :label="__('Remember Me')" />
        <x-form-actions :submitLabel="__('Sign in')" :largeSubmit="true" />
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
