<?php

$gameID = getRandomGameWithAchievements();

return redirect(url("/game/$gameID"));
