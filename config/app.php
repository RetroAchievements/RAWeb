<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    'version' => env('APP_VERSION', 'DEV'),
    'branch' => env('APP_BRANCH', 'master'),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', env('APP_URL')),

    /*
     * Media is served independently of other static assets (asset_url)
     */
    'media_url' => env('MEDIA_URL', env('APP_URL') . '/media'),

    /*
     * The "Web" API for public data
     */
    'api_url' => env('API_URL', env('APP_URL') . '/api'),

    /*
     * Integrations use the Connect API
     */
    'connect_url' => env('CONNECT_URL', env('APP_URL') . '/api/connect'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    'timezones' => collect(DateTimeZone::listIdentifiers())->mapWithKeys(fn ($item) => [$item => $item])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en', // TODO change to en_US

    'locales' => [
        // 'cn_' => '简体中文 (Simplified Chinese)',
        // 'cn_' => '繁體中文 (Traditional Chinese)',
        // 'jp' => '日本語 (Japanese)',
        // 'kr' => '한국어 (Korean)',
        // '' => 'ไทย (Thai)',
        // '' => 'Български (Bulgarian)',
        // '' => 'Čeština (Czech)',
        // '' => 'Dansk (Danish)',
        'de' => 'Deutsch (German)',
        'en' => 'English (US)', // TODO rename to en_US
        'en_GB' => 'English (GB)',
        // 'es_ES' => 'Español - España (Spanish - Spain)',
        // 'es_' => 'Español - Latinoamérica (Spanish - Latin America)',
        // '' => 'Ελληνικά (Greek)',
        // 'fr_FR' => 'Français (French)',
        // 'it_IT' => 'Italiano (Italian)',
        // 'hu' => 'Magyar (Hungarian)',
        // 'nl' => 'Nederlands (Dutch)',
        // '' => 'Norsk (Norwegian)',
        // '' => 'Polski (Polish)',
        // 'pt_PT' => 'Português (Portuguese)',
        'pt_BR' => 'Português - Brasil (Portuguese - Brazil)',
        // '' => 'Română (Romanian)',
        // 'ru' => 'Русский (Russian)',
        // '' => 'Suomi (Finnish)',
        // '' => 'Svenska (Swedish)',
        // '' => 'Türkçe (Turkish)',
        // '' => 'Tiếng Việt (Vietnamese)',
        // '' => 'Українська (Ukrainian)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en', // TODO change to en_US

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    // TODO remove as soon as all passwords have been migrated
    'legacy_password_salt' => env('RA_PASSWORD_SALT', 'SaltySaltySaltFace'),

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store' => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */
        Jenssegers\Agent\AgentServiceProvider::class,

        /*
        * Application Service Providers...
        */

        /*
         * Api & Connect
         * Come first to make sure subdomain routes are registered first
         */
        App\Connect\RouteServiceProvider::class,
        App\Api\RouteServiceProvider::class,

        /*
         * Platform Service Providers
         */
        App\Platform\AppServiceProvider::class,
        App\Platform\AuthServiceProvider::class,
        App\Platform\EventServiceProvider::class,
        App\Platform\RouteServiceProvider::class,

        /*
         * Community Service Providers
         */
        App\Community\AppServiceProvider::class,
        App\Community\AuthServiceProvider::class,
        App\Community\EventServiceProvider::class,
        App\Community\RouteServiceProvider::class,

        /*
         * Support Service Providers
         */
        App\Support\Filesystem\FilesystemServiceProvider::class,
        App\Support\HashId\HashIdServiceProvider::class,
        App\Support\Settings\SettingsServiceProvider::class,
        App\Support\Sync\SyncServiceProvider::class,

        /*
         * Site Providers
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\FortifyServiceProvider::class,
        App\Providers\HorizonServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\VoltServiceProvider::class,
        App\Providers\FolioServiceProvider::class,

        /*
         * Admin
         */
        App\Filament\FilamentServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'Agent' => Jenssegers\Agent\Facades\Agent::class,
    ])->toArray(),

];
