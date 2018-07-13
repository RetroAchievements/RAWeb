#!/usr/bin/env bash

ssh -T deploy-web-v1@retroachievements.org << EOF
    cd /var/www/v1.retroachievements.org
    git pull -v
    php composer.phar install --no-dev
EOF
