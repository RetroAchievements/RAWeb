<?php

function RenderUpdateSubscriptionForm(
    string $formID,
    string $subjectType,
    int $subjectID,
    bool $isSubscribed,
    ?string $resource = null
): void {
    echo "<form id='$formID' action='/request/user/update-subscription.php' method='post'>";
    echo csrf_field();
    echo "<input type='hidden' name='subject_type' value='$subjectType'/>";
    echo "<input type='hidden' name='subject_id' value='$subjectID'/>";
    echo "<input type='hidden' name='operation' value='" . ($isSubscribed ? "unsubscribe" : "subscribe") . "'/>";
    echo "<button class='btn'>" . ($isSubscribed ? "Unsubscribe" . ($resource ? ' from ' : '') : "Subscribe" . ($resource ? ' to ' : '')) . ($resource ?: '') . "</button>";
    echo "</form>";
}
