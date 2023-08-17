<x-input.input
    :model="$model ?? null"
    type="file"
    :attribute="$attribute ?? 'file'"
    :icon="$icon ?? 'file'"
    :required="$required ?? false"
    :disabled="$disabled ?? false"
    :help="$help ?? false"
/>
@if(!empty($model) && $model->hasMedia($attribute))
    <div class="flex">
        <div class="lg:col-9 ml-auto">
            <x-media.file :media="$model->getMedia($attribute)->last()" />
        </div>
    </div>
@endif
