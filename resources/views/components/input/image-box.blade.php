<div class="box">
    <div class="box-header">
        <h3 class="box-title">
            {{ __('validation.attributes.'.$attribute) }}
        </h3>
    </div>
    <div class="box-body">
        <div class="text-center">
            @if($model->getAttribute($attribute))
                <img class="img-responsive img-thumbnail" src="{{ $model->getAttribute($attribute) }}" style="display:inline-block">
            @else
                <p>
                    <i>No image</i>
                </p>
            @endif
        </div>
    </div>
</div>
