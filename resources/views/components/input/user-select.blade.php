@props([
    'name' => '',
    'user' => '',
])

<script>
function onUserChange(fieldName) {
    var fieldValue = $('#' + fieldName).val();
    if (fieldValue.length > 2) {
        var fieldIcon = $('.' + fieldName + 'Icon');
        if (fieldIcon) {
            fieldIcon.attr('src', mediaAsset('/UserPic/' + fieldName + '.png'));
        }
    }
}
</script>

<input type="text" name="{{ $name }}" id="{{ $name }}"
    class="searchuser" value="{{ $user }}"
    onblur="onUserChange('{{ $name }}'); return false;" />
