<?php

use LegacyApp\Site\Models\StaticData;

$staticData = StaticData::first();

if ($staticData === null) {
    return;
}

$achID = $staticData['Event_AOTW_AchievementID'];
$forumTopicID = $staticData['Event_AOTW_ForumID'];

$achData = GetAchievementData($achID);
if (empty($achData)) {
    return;
}
?>
<div class="component">
    <h3>Achievement of the Week</h3>
    <div class="text-center">
        <div>
            {!! achievementAvatar($achData) !!}
        </div>
        in
        <div>
            {!! gameAvatar($achData, iconSize: 32) !!}
        </div>
        <a class="btn mt-2" href="/viewtopic.php?t={{ $forumTopicID }}">Join this tournament!</a>
    </div>
</div>
