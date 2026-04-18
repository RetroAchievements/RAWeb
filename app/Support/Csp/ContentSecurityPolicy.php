<?php

declare(strict_types=1);

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Value;

class ContentSecurityPolicy extends Policy
{
    public function configure(): void
    {
        // $this->reportTo(route('api.csp'));

        $this
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::STYLE, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF);

        /*
         * not adding nonce for style tags otherwise unsafe inline won't work
         * $this->addNonceForDirective(Directive::STYLE);
         */
        $this->add(Directive::STYLE, Keyword::UNSAFE_INLINE);

        $this->add(Directive::BLOCK_ALL_MIXED_CONTENT, Value::NO_VALUE);

        if (!app()->environment('local')) {
            /*
             * be strict on any other environment than local
             */
            $this->add(Directive::UPGRADE_INSECURE_REQUESTS, Value::NO_VALUE);

            /*
             * TODO: revert this as soon as livewire supports csp nonce
             * see https://github.com/livewire/livewire/issues/650
             */
            // $this->addNonceForDirective(Directive::SCRIPT);
            $this->add(Directive::SCRIPT, Keyword::UNSAFE_EVAL);
            $this->add(Directive::SCRIPT, Keyword::UNSAFE_INLINE);
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
            $this->add(Directive::SCRIPT, Keyword::UNSAFE_EVAL);
            $this->add(Directive::SCRIPT, Keyword::UNSAFE_INLINE);
        }

        /*
         * allow static asset host
         */
        if (config('app.asset_url') !== null) {
            $this->add(Directive::STYLE, config('app.asset_url'));
            $this->add(Directive::SCRIPT, config('app.asset_url'));
            $this->add(Directive::IMG, config('app.asset_url'));
            $this->add(Directive::MEDIA, config('app.asset_url'));
            $this->add(Directive::FONT, config('app.asset_url'));
            $this->add(Directive::CONNECT, config('app.asset_url'));
        }

        /*
         * allow media asset host
         */
        $this->add(Directive::IMG, config('app.media_url'));
        $this->add(Directive::MEDIA, config('app.media_url'));
        // $this->addDirective(Directive::IMG, 'i.retroachievements.org');

        /*
         * data urls for inline svg images & fonts
         */
        $this->add(Directive::IMG, 'data:');
        $this->add(Directive::FONT, 'data:');

        /*
         * websockets
         */
        $this->add(
            Directive::CONNECT,
            (config('websockets.ssl.local_cert') ? 'wss' : 'ws') . '://'
            . request()->getHost() . ':' . config('broadcasting.connections.pusher.options.port')
        );

        /*
         * websockets dashboard
         * note: websockets routes are excluded from csp middleware
         */
        // $this->add(Directive::SCRIPT, 'cdn.plot.ly');
        // $this->add(Directive::SCRIPT, 'cdn.jsdelivr.net');
        // $this->add(Directive::SCRIPT, 'js.pusher.com');
        // $this->add(Directive::SCRIPT, 'code.jquery.com');
        // $this->add(Directive::STYLE, 'stackpath.bootstrapcdn.com');

        /*
         * route-usage
         * note: horizon has its assets published
         */
        // $this->add(Directive::STYLE, 'unpkg.com');

        /*
         * external resources on-site
         */
        // $this->add(Directive::SCRIPT, 'ajax.googleapis.com');
        // $this->add(Directive::STYLE, 'ajax.googleapis.com');
        $this->add(Directive::IMG, 'dl.dropboxusercontent.com');
        $this->add(Directive::STYLE, 'fonts.googleapis.com');
        $this->add(Directive::FONT, 'fonts.gstatic.com');
        $this->add(Directive::IMG, 'i.imgur.com');
        $this->add(Directive::IMG, 'cdn.discordapp.com');
        $this->add(Directive::IMG, 'media.discordapp.net');
        $this->add(Directive::IMG, '*.photobucket.com');
        $this->add(Directive::FRAME, '*.youtube-nocookie.com');
        $this->add(Directive::FRAME, 'player.twitch.tv');
        $this->add(Directive::SCRIPT, 'cdn.jsdelivr.net');
    }
}
