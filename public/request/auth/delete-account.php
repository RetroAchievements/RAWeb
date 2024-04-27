<?php

use App\Platform\Actions\RequestAccountDeletion;

if (!request()->user()) {
    return back()->withErrors(__('legacy.error.error'));
}

$action = new RequestAccountDeletion();
if ($action->execute(request()->user())) {
    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
