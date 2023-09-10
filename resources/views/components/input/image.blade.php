<x-input.file :model="$model"
              :attribute="$attribute ?? 'image'"
              :icon="$icon ?? 'image'"
              :required="$required ?? false"
/>
@if(!empty($model) && $model->hasMedia($attribute))
    <div class="flex">
        <div class="lg:col-9 ml-auto">
            <x-media.image :media="$model->getMedia($attribute)->last()" />
        </div>
    </div>
@endif

