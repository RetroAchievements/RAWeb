<?php

$gameID = getRandomGameWithAchievements();

abort_with(redirect(route('game.show', $gameID)));
