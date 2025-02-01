<?php

/*
 *  API_GetUserPoints
 *    u : username
 *    i : user ULID
 *
 *  int        Points                  number of hardcore points the user has
 *  int        SoftcorePoints          number of softcore points the user has
 */

 use App\Models\User;
 use App\Support\Rules\CtypeAlnum;
 use Illuminate\Support\Arr;
 use Illuminate\Support\Facades\Validator;

 $input = Validator::validate(Arr::wrap(request()->query()), [
     'u' => ['required_without:i', 'min:2', 'max:20', new CtypeAlnum()],
     'i' => ['required_without:u', 'string', 'size:26'],
 ]);

$foundUser = isset($input['i'])
    ? User::whereUlid($input['i'])->first()
    : User::whereName($input['u'])->first();

if (!$foundUser) {
    return response()->json([], 404);
}

return response()->json(array_map('intval', [
    'Points' => $foundUser->points,
    'SoftcorePoints' => $foundUser->points_softcore,
]));
