<?php
use App\Enums\Permissions;
use Illuminate\Support\Carbon;
?>

@props([
    'authorUserName' => null,
    'authorJoinDate' => null,
    'authorVisibleRoles' => null, // ?Collection|Role[]
    'isAuthorDeleted' => false,
])

<?php
// "January 4, 2012"
$formattedUserJoinDate = Carbon::parse($authorJoinDate)->format('M j, Y');

$roleLabel = null;
if (isset($authorVisibleRoles) && $authorVisibleRoles->count() > 0) {
    $roleLabel = __('permission.role.' . $authorVisibleRoles->get(0)->name);
}
?>

<div class='lg:py-2 px-0.5 {{ $authorUserName ? 'border-b pb-2' : '' }} lg:border-b-0 lg:border-r border-neutral-700'>
    <div class='flex lg:flex-col lg:text-center items-center w-full lg:w-44'>
        @if ($authorUserName)
            {!! userAvatar($authorUserName, label: false, iconSize: 72, iconClass: 'rounded-sm', tooltip: true) !!}

            <div class='ml-2 lg:ml-0'>
                <div class='mb-[2px] lg:mt-1'>
                    {!! userAvatar($authorUserName, icon: false, tooltip: true) !!}
                </div>

                @if ($roleLabel)
                    <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>
                        {{ $roleLabel }}
                    </p>
                @endif

                @if ($authorJoinDate && !$isAuthorDeleted)
                    <p class='smalltext !leading-4 !text-xs lg:!text-2xs'>
                        Joined {{ $formattedUserJoinDate }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
