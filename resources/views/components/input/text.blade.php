<x-input.input
    :model="$model ?? null"
    :type="$type ?? 'text'"
    :attribute="$attribute ?? 'text'"
    :icon="$icon ?? false"
    :required="$required ?? false"
    :disabled="$disabled ?? false"
    :readonly="$readonly ?? false"
    :help="$help ?? false"
/>
