#!/usr/bin/env bash

ssh -T deploy-web@retroachievements.org << EOF
    cd /var/www/stage.retroachievements.org
    php artisan down
    git pull -v
    php composer.phar install --no-dev
    php artisan config:cache
    php artisan route:cache
    php artisan up
EOF
