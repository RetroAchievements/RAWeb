<?php

declare(strict_types=1);

namespace App\Site;

use App\Site\Actions\CreateNewUser;
use App\Site\Actions\ResetUserPassword;
use App\Site\Actions\UpdateUserPassword;
use App\Site\Actions\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\ConfirmablePasswordController;
use Laravel\Fortify\Http\Controllers\ConfirmedPasswordStatusController;
use Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationNotificationController;
use Laravel\Fortify\Http\Controllers\EmailVerificationPromptController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\ProfileInformationController;
use Laravel\Fortify\Http\Controllers\RecoveryCodeController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController;
use Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController;
use Laravel\Fortify\Http\Controllers\TwoFactorSecretKeyController;
use Laravel\Fortify\Http\Controllers\VerifyEmailController;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->mapAuthRoutes();
        $this->mapSettingsRoutes();

        Fortify::viewPrefix('auth.');
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by($request->username . $request->ip()));
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }

    private function mapAuthRoutes(): void
    {
        Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {
            Route::get('/login', [AuthenticatedSessionController::class, 'create'])
                ->middleware(['guest:' . config('fortify.guard')])
                ->name('login');

            $limiter = config('fortify.limiters.login');
            $verificationLimiter = config('fortify.limiters.verification', '6,1');

            Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->middleware(array_filter([
                        'guest:' . config('fortify.guard'),
                        $limiter ? 'throttle:' . $limiter : null,
                    ])
                );

            Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');

            // Password Reset...
            if (Features::enabled(Features::resetPasswords())) {
                Route::get('/password/forgot', [PasswordResetLinkController::class, 'create'])
                    ->middleware(['guest:' . config('fortify.guard')])
                    ->name('password.request');

                Route::post('/password/forgot', [PasswordResetLinkController::class, 'store'])
                    ->middleware(['guest:' . config('fortify.guard')])
                    ->name('password.email');

                Route::get('/password/reset/{token}', [NewPasswordController::class, 'create'])
                    ->middleware(['guest:' . config('fortify.guard')])
                    ->name('password.reset');

                Route::post('/password/reset', [NewPasswordController::class, 'store'])
                    ->middleware(['guest:' . config('fortify.guard')])
                    ->name('password.update');
            }

            // Registration...
            if (Features::enabled(Features::registration())) {
                Route::get('/register', [RegisteredUserController::class, 'create'])
                    ->middleware(['guest:' . config('fortify.guard')])
                    ->name('register');

                Route::post('/register', [RegisteredUserController::class, 'store'])
                    ->middleware(['guest:' . config('fortify.guard')]);
            }

            // Email Verification...
            if (Features::enabled(Features::emailVerification())) {
                Route::get('/email/verify', [EmailVerificationPromptController::class, '__invoke'])
                    ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
                    ->name('verification.notice');

                Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                    ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard'), 'signed', 'throttle:' . $verificationLimiter])
                    ->name('verification.verify');

                Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                    ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard'), 'throttle:' . $verificationLimiter])
                    ->name('verification.send');
            }
        });
    }

    private function mapSettingsRoutes(): void
    {
        Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {
            $twoFactorLimiter = config('fortify.limiters.two-factor');

            // Profile Information...
            if (Features::enabled(Features::updateProfileInformation())) {
                Route::put('/settings/profile', [ProfileInformationController::class, 'update'])
                    ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
                    ->name('user-profile-information.update');
            }

            // Passwords...
            if (Features::enabled(Features::updatePasswords())) {
                Route::put('/auth/password', [PasswordController::class, 'update'])
                    ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
                    ->name('user-password.update');
            }

            // Password Confirmation...
            Route::get('/auth/password/confirm', [ConfirmablePasswordController::class, 'show'])
                ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')]);

            Route::get('/auth/password/confirmed-status', [ConfirmedPasswordStatusController::class, 'show'])
                ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
                ->name('password.confirmation');

            Route::post('/auth/password/confirm', [ConfirmablePasswordController::class, 'store'])
                ->middleware([config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')])
                ->name('password.confirm');

            // Two Factor Authentication...
            if (Features::enabled(Features::twoFactorAuthentication())) {
                Route::get('/auth/two-factor/challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])
                    ->middleware(['guest:' . config('fortify.guard')])
                    ->name('two-factor.login');

                Route::post('/auth/two-factor/challenge', [TwoFactorAuthenticatedSessionController::class, 'store'])
                    ->middleware(array_filter([
                            'guest:' . config('fortify.guard'),
                            $twoFactorLimiter ? 'throttle:' . $twoFactorLimiter : null,
                        ])
                    );

                $twoFactorMiddleware = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
                    ? [config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard'), 'password.confirm']
                    : [config('fortify.auth_middleware', 'auth') . ':' . config('fortify.guard')];

                Route::post('/auth/two-factor/authentication', [TwoFactorAuthenticationController::class, 'store'])
                    ->middleware($twoFactorMiddleware)
                    ->name('two-factor.enable');

                Route::post('/auth/two-factor/confirmed-authentication', [ConfirmedTwoFactorAuthenticationController::class, 'store'])
                    ->middleware($twoFactorMiddleware)
                    ->name('two-factor.confirm');

                Route::delete('/auth/two-factor/authentication', [TwoFactorAuthenticationController::class, 'destroy'])
                    ->middleware($twoFactorMiddleware)
                    ->name('two-factor.disable');

                Route::get('/settings/two-factor/qr-code', [TwoFactorQrCodeController::class, 'show'])
                    ->middleware($twoFactorMiddleware)
                    ->name('two-factor.qr-code');

                Route::get('/settings/two-factor/secret-key', [TwoFactorSecretKeyController::class, 'show'])
                    ->middleware($twoFactorMiddleware)
                    ->name('two-factor.secret-key');

                Route::get('/settings/two-factor/recovery-codes', [RecoveryCodeController::class, 'index'])
                    ->middleware($twoFactorMiddleware)
                    ->name('two-factor.recovery-codes');

                Route::post('/settings/two-factor/recovery-codes', [RecoveryCodeController::class, 'store'])
                    ->middleware($twoFactorMiddleware);
            }
        });
    }
}
