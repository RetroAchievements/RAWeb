<?php
use LegacyApp\Site\Enums\Permissions;

?>

@props([
    'authorUserName',
    'authorJoinDate' => '',
    'authorPermissions' => Permissions::Unregistered,
    'authorPostCount' => 0,
])

<?php
$canShowUserTooltip = $authorPermissions >= Permissions::Unregistered;
?>

<div class='pb-2 lg:py-2 px-0.5 border-b lg:border-b-0 lg:border-r border-neutral-700'>
    <div class='flex lg:flex-col lg:text-center items-center w-full lg:w-44'>
        @if($canShowUserTooltip)
            {!! userAvatar($authorUserName, label: false, iconSize: 72, iconClass: 'rounded-sm', tooltip: $canShowUserTooltip) !!}
        @endif

        <div class='ml-2 lg:ml-0'>
            <div class='mb-[2px] lg:mt-1'>
                @if($canShowUserTooltip)
                    {!! userAvatar($authorUserName, icon: false, tooltip: $canShowUserTooltip) !!}
                @else
                    <span class='line-through'>{{ $authorUserName }}</span>
                @endif
            </div>

            @if($authorPermissions != Permissions::Registered)
                <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>{{ Permissions::toString($authorPermissions) }}</p>
            @endif

            @if($canShowUserTooltip)
                <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>{{ localized_number($authorPostCount) }} {{ __res('user.post', $authorPostCount) }}</p>

                @if($authorJoinDate)
                    <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>Joined {{ $authorJoinDate }}</p>
                @endif
            @endif
        </div>
    </div>
</div>