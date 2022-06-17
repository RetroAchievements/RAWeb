<x-input.input
    :model="$model ?? null"
    type="email"
    :attribute="$attribute ?? 'email'"
    :label="$label ?? null"
    :icon="$icon ?? 'envelope'"
    :required="$required ?? false"
    :disabled="$disabled ?? false"
    :readonly="$readonly ?? false"
/>
