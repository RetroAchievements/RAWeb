<?php

use RA\ArticleType;

$subscribers = getSubscribersOfUserWall(1, 'luchaos');
dump($subscribers);
$subscribers = getSubscribersOfArticle(ArticleType::User, 1, null, false);
dump($subscribers);
$subscribers = getSubscribersOfArticle(ArticleType::User, 1);
dump($subscribers);
