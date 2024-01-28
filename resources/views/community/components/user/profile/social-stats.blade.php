@props([
    'socialStats' => [],
    'username' => '',
])

<?php
$setRequests = $socialStats['userSetRequestInformation'];
?>

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Social</p>
<div class="relative w-full p-2 bg-embed rounded">
    <div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
        <x-user.profile.stat-element label="Forum posts">
            @if (!$socialStats['numForumPosts'])
                0
            @else
                <a href="{{ '/forumposthistory.php?u=' . $username }}" class="font-bold">
                    {{ localized_number($socialStats['numForumPosts']) }}
                </a> 
            @endif
        </x-user.profile.stat-element>

        <x-user.profile.stat-element label="Achievement sets requested">
            @if ($setRequests['total'] === 0)
                0
            @elseif ($setRequests['used'] === 0)
                <span>
                    0
                    
                    @if ($setRequests['remaining'] > 0)
                        ({{ $setRequests['remaining'] }} left)
                    @endif
                </span>
            @else
                <a class="font-bold" href="{{ '/setRequestList.php?u=' . $username }}">
                    {{ $setRequests['used'] }}
                    @if ($setRequests['remaining'] > 0)
                        ({{ $setRequests['remaining'] }} left)
                    @endif
                </a>
            @endif
        </x-user.profile.stat-element>
    </div>
</div>
