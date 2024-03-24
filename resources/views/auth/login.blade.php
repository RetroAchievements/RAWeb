<x-prompt-layout
    :page-title="__('Login')"
>
    <x-slot name="header">
        <h1 class="text-h4 mb-0">{{ __('Sign in to RetroAchievements') }}</h1>
    </x-slot>

    <x-base.form :action="route('login')">
        <div class="flex flex-col gap-y-3">
            <x-base.form.input name="User" label="Username" requiredSilent />
            <x-base.form.input.password name="password" requiredSilent />
            <x-base.form.input.checkbox name="remember" :label="__('Remember Me')" />
            <x-base.form-actions :submitLabel="__('Sign in')" :largeSubmit="true" />
            <div class="flex flex-col justify-between">
                <a class="btn btn-link" href="{{ url('resetPassword.php') }}">
                    {{ __('Forgot your password?') }}
                </a>
                <a class="btn btn-link" href="{{ url('createaccount.php') }}">
                    {{ __('Sign up') }}
                </a>
            </div>
        </div>
    </x-base.form>
</x-prompt-layout>
