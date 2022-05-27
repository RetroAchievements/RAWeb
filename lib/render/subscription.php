<?php

function RenderUpdateSubscriptionForm($formID, $subjectType, $subjectID, $isSubscribed): void
{
    echo "<form id='$formID' action='/request/user/update-subscription.php' method='post'>";
    echo "<input type='hidden' name='return_url' value='" . $_SERVER["REQUEST_URI"] . "'/>";
    echo "<input type='hidden' name='subject_type' value='$subjectType'/>";
    echo "<input type='hidden' name='subject_id' value='$subjectID'/>";
    echo "<input type='hidden' name='operation' value='" . ($isSubscribed ? "unsubscribe" : "subscribe") . "'/>";
    echo "</form>";
}
