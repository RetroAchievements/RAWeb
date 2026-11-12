<?php

// TODO migrate to Fortify

authenticateFromCookie($user, $permissions, $userDetails);

if (request()->user()) {
    abort_with(redirect(route('home')));
}

$turnstileTheme = match (request()->cookie('scheme', '')) {
    'light' => 'light',
    'system' => 'auto',
    default => 'dark',
};
?>
<x-app-layout pageTitle="Create Account">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <h3>Create Account</h3>
    <div class="infobox">
        <form method=post action="/request/auth/register.php" x-data="{ isSending: false }" x-on:submit="isSending = true">
            <?= csrf_field() ?>
            <table>
                <tbody>
                <tr>
                    <td class="text-right"><label for="username">Username</label></td>
                    <td>
                        <div><input type="text" id="username" name="username" value="<?= old('username') ?>" required></div>
                    </td>
                </tr>
                <tr>
                    <td class="text-right"><label for="email">Email</label></td>
                    <td>
                        <div><input type="text" id="email" name="email" value="<?= old('email') ?>" required></div>
                    </td>
                </tr>
                <tr>
                    <td class="text-right"><label for="email_confirmation">Confirm Email</label></td>
                    <td>
                        <div><input type="text" id="email_confirmation" name="email_confirmation" value="<?= old('email_confirmation') ?>" required></div>
                    </td>
                </tr>
                <tr>
                    <td class="text-right"><label for="password">Password</label></td>
                    <td>
                        <div><input type="password" id="password" name="password" required></div>
                    </td>
                </tr>
                <?php if (config('services.cloudflare.turnstile_site_key')): ?>
                    <tr>
                        <td class="text-right"><label for="captcha">Are you a robot?</label></td>
                        <td>
                            <div class="cf-turnstile" data-sitekey="<?= config('services.cloudflare.turnstile_site_key') ?>" data-theme="<?= $turnstileTheme ?>"></div>
                        </td>
                    </tr>
                <?php endif ?>
                <tr>
                    <td></td>
                    <td>
                        <input type="checkbox" name="terms" required> I agree to the <a href="<?= route('terms') ?>">Terms and Conditions</a>.<br>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <button class="btn" :disabled="isSending">Create User</button>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <?= __('Already have an account?') ?> <a href="<?= route('login') ?>"><?= __('Sign in') ?></a>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</x-app-layout>
