<?php

use Illuminate\Support\Facades\Auth;

Auth::guard()->logout();

request()->session()->invalidate();
request()->session()->regenerateToken();

return redirect(route('home'));
