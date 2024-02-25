<x-base.form.input.password {{ $attributes }} />
<x-base.form.input {{ $attributes->merge([
    'type' => 'password',
    'name' => 'password_confirmation',
    'icon' => 'lock',
]) }} />
