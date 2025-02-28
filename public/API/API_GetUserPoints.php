<?php

/*
 *  API_GetUserPoints
 *    u : username or user ULID
 *
 *  int        Points                  number of hardcore points the user has
 *  int        SoftcorePoints          number of softcore points the user has
 */

use App\Actions\FindUserByIdentifierAction;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

 $input = Validator::validate(Arr::wrap(request()->query()), [
     'u' => ['required', new ValidUserIdentifier()],
 ]);

$foundUser = (new FindUserByIdentifierAction())->execute($input['u']);

if (!$foundUser) {
    return response()->json([], 404);
}

return response()->json(array_map('intval', [
    'Points' => $foundUser->points,
    'SoftcorePoints' => $foundUser->points_softcore,
]));
