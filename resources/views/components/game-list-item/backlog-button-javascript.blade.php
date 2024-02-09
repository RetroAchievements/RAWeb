<?php
    $addButtonText = __('user-game-list.play.add');
    $removeButtonText = __('user-game-list.play.remove');
?>
<script>
function togglePlayListItem(id)
{
    $.post('/request/user-game-list/toggle.php', {
        type: 'play',
        game: id
    })
    .done(function () {
        $("#add-to-list-" + id).toggle();
        $("#remove-from-list-" + id).toggle();
        if ($("#add-to-list-" + id).is(':visible')) {
            $("#play-list-button-" + id).prop('title', '{{ $addButtonText }}');
        } else {
            $("#play-list-button-" + id).prop('title', '{{ $removeButtonText }}');
        }
    });
}
</script>
