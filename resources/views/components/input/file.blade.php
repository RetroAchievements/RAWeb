<x-input.text {{ $attributes->merge([
    'type' => 'file',
    'icon' => 'file',
])}} />
<?php
// TODO reactive x-media.file component as soon as media library is in use
// @if(!empty($model) && $model->hasMedia($name))
//     <div class="flex">
//         <div class="lg:col-9 ml-auto">
//             <x-media
// .file :media="$model->getMedia($name)->last()" />
//         </div>
//     </div>
// @endif
?>
