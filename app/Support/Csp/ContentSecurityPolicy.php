<?php

declare(strict_types=1);

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Policy;
use Spatie\Csp\Value;

class ContentSecurityPolicy extends Policy
{
    public function configure(): void
    {
        // $this->reportTo(route('api.csp'));

        $this
            ->addDirective(Directive::BASE, Keyword::SELF)
            ->addDirective(Directive::CONNECT, Keyword::SELF)
            ->addDirective(Directive::DEFAULT, Keyword::SELF)
            ->addDirective(Directive::FORM_ACTION, Keyword::SELF)
            ->addDirective(Directive::IMG, Keyword::SELF)
            ->addDirective(Directive::MEDIA, Keyword::SELF)
            ->addDirective(Directive::OBJECT, Keyword::NONE)
            ->addDirective(Directive::SCRIPT, Keyword::SELF)
            ->addDirective(Directive::STYLE, Keyword::SELF)
            ->addDirective(Directive::FONT, Keyword::SELF);

        /*
         * not adding nonce for style tags otherwise unsafe inline won't work
         * $this->addNonceForDirective(Directive::STYLE);
         */
        $this->addDirective(Directive::STYLE, Keyword::UNSAFE_INLINE);

        $this->addDirective(Directive::BLOCK_ALL_MIXED_CONTENT, Value::NO_VALUE);

        if (!app()->environment('local')) {
            /*
             * be strict on any other environment than local
             */
            $this->addDirective(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE);

            /*
             * TODO: revert this as soon as livewire supports csp nonce
             * see https://github.com/livewire/livewire/issues/650
             */
            // $this->addNonceForDirective(Directive::SCRIPT);
            $this->addDirective(Directive::SCRIPT, Keyword::UNSAFE_EVAL);
            $this->addDirective(Directive::SCRIPT, Keyword::UNSAFE_INLINE);
        }

        if (app()->environment('local')) {
            /*
             * do not add nonce if unsafe-inline should work
             * kept here for reference and to test that it will work on prod
             */
            // $this->addNonceForDirective(Directive::SCRIPT);

            /*
             * make dump() & DebugBar work
             */
            $this->addDirective(Directive::SCRIPT, Keyword::UNSAFE_EVAL);
            $this->addDirective(Directive::SCRIPT, Keyword::UNSAFE_INLINE);
        }

        /*
         * allow static asset host
         */
        if (config('app.asset_url') !== null) {
            $this->addDirective(Directive::STYLE, config('app.asset_url'));
            $this->addDirective(Directive::SCRIPT, config('app.asset_url'));
            $this->addDirective(Directive::IMG, config('app.asset_url'));
            $this->addDirective(Directive::MEDIA, config('app.asset_url'));
            $this->addDirective(Directive::FONT, config('app.asset_url'));
            $this->addDirective(Directive::CONNECT, config('app.asset_url'));
        }

        /*
         * allow media asset host
         */
        $this->addDirective(Directive::IMG, config('app.media_url'));
        $this->addDirective(Directive::MEDIA, config('app.media_url'));
        // $this->addDirective(Directive::IMG, 'i.retroachievements.org');

        /*
         * data urls for inline svg images & fonts
         */
        $this->addDirective(Directive::IMG, 'data:');
        $this->addDirective(Directive::FONT, 'data:');

        /*
         * websockets
         */
        $this->addDirective(
            Directive::CONNECT,
            (config('websockets.ssl.local_cert') ? 'wss' : 'ws') . '://'
            . request()->getHost() . ':' . config('broadcasting.connections.pusher.options.port')
        );

        /*
         * websockets dashboard
         * note: websockets routes are excluded from csp middleware
         */
        // $this->addDirective(Directive::SCRIPT, 'cdn.plot.ly');
        // $this->addDirective(Directive::SCRIPT, 'cdn.jsdelivr.net');
        // $this->addDirective(Directive::SCRIPT, 'js.pusher.com');
        // $this->addDirective(Directive::SCRIPT, 'code.jquery.com');
        // $this->addDirective(Directive::STYLE, 'stackpath.bootstrapcdn.com');

        /*
         * route-usage
         * note: horizon has its assets published
         */
        // $this->addDirective(Directive::STYLE, 'unpkg.com');

        /*
         * external resources on-site
         */
        // $this->addDirective(Directive::SCRIPT, 'ajax.googleapis.com');
        // $this->addDirective(Directive::STYLE, 'ajax.googleapis.com');
        $this->addDirective(Directive::IMG, 'dl.dropboxusercontent.com');
        $this->addDirective(Directive::STYLE, 'fonts.googleapis.com');
        $this->addDirective(Directive::FONT, 'fonts.gstatic.com');
        $this->addDirective(Directive::IMG, 'i.imgur.com');
        $this->addDirective(Directive::IMG, 'cdn.discordapp.com');
        $this->addDirective(Directive::IMG, 'media.discordapp.net');
        $this->addDirective(Directive::IMG, '*.photobucket.com');
        $this->addDirective(Directive::FRAME, '*.youtube-nocookie.com');
        $this->addDirective(Directive::FRAME, 'player.twitch.tv');
        $this->addDirective(Directive::SCRIPT, 'cdn.jsdelivr.net');
    }
}
