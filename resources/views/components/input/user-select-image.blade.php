@props([
    'for' => '',
    'user' => '',
    'size' => 64,
])

<?php
    if (empty($user)) {
        $user = '_User';
    }
    $imageSource = media_asset("/UserPic/$user.png");
?>

<img class='searchusericon' src='{{ $imageSource }}'
     width='{{ $size }}' height='{{ $size }}' />
