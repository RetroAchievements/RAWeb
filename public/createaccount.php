<?php

authenticateFromCookie($user, $permissions, $userDetails);

if (request()->user()) {
    return redirect(route('home'));
}

RenderContentStart("Create Account");
?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<article>
    <h3>Create Account</h3>
    <div class="infobox">
        <form method=post action="/request/auth/register.php">
            <?= csrf_field() ?>
            <table>
                <tbody>
                <tr>
                    <td class="text-right"><label for="username">Username</label></td>
                    <td>
                        <div><input type="text" id="username" name='username' value="<?= old('username') ?>"></div>
                    </td>
                </tr>
                <tr>
                    <td class="text-right"><label for="email">Email</label></td>
                    <td>
                        <div><input type="text" id="email" name='email' value="<?= old('email') ?>"></div>
                    </td>
                </tr>
                <tr>
                    <td class="text-right"><label for="email_confirmation">Confirm Email</label></td>
                    <td>
                        <div><input type="text" id="email_confirmation" name='email_confirmation' value="<?= old('email_confirmation') ?>"></div>
                    </td>
                </tr>
                <tr>
                    <td class="text-right"><label for="password">Password</label></td>
                    <td>
                        <div><input type="password" id="password" name='password'></div>
                    </td>
                </tr>
                <?php if (config('services.google.recaptcha_key')): ?>
                    <tr>
                        <td class="text-right"><label for="captcha">Are you a robot?</label></td>
                        <td>
                            <div class="g-recaptcha" data-sitekey="<?= config('services.google.recaptcha_key') ?>"></div>
                        </td>
                    </tr>
                <?php endif ?>
                <tr>
                    <td></td>
                    <td>
                        By clicking 'Create User', you agree to the <a href="<?= route('terms') ?>">Terms and Conditions</a>.<br>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <button class="btn">Create User</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</article>
<?php RenderContentEnd(); ?>
