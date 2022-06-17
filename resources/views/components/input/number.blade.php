<x-input.input :model="$model ?? null"
               type="number"
               :attribute="$attribute ?? 'amount'"
               :icon="$icon ?? 'sort-numeric-asc'"
               :disabled="$disabled ?? null" />
