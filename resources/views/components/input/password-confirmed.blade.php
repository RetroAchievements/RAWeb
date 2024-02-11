<x-input.password {{ $attributes }} />
<x-input.text {{ $attributes->merge([
    'type' => 'password',
    'name' => 'password_confirmation',
    'icon' => 'lock',
]) }} />
