<?php

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

if (getControlPanelUserInfo($user, $userData)) {
    return response()->json($userData['Played']);
}

abort(400);
