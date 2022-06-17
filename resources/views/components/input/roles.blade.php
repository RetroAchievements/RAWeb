<?php
$assignableRoles = request()->user()
    ->assignableRoles
    ->mapWithKeys(fn ($roleName) => [$roleName => __('permission.role.' . $roleName)]);
$selected = $model ? $model->roles()->get()->pluck('name')->toArray() : [];
?>
<x-input.selectpicker
    :model="$model"
    :attribute="$attribute"
    :options="$assignableRoles"
    :selected="$selected"
/>
