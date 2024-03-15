<x-base.form.input.file {{ $attributes->merge([
    'icon' => 'image',
]) }} />
<?php
// TODO reactive x-media.image component as soon as media library is in use
// @if(!empty($model) && $model->hasMedia($attribute))
//     <div class="flex">
//         <div class="lg:col-9 ml-auto">
//             <x-media
// .image :media="$model->getMedia($attribute)->last()" />
//         </div>
//     </div>
// @endif
?>
