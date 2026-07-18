<?php
use App\Community\Enums\CommentableType;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
use App\Models\ConnectWarning;
use App\Models\Role;
?>

@props([
    'targetUser' => null, // User
])

<?php
$isTargetUserUntracked = $targetUser->unranked_at !== null;
$targetUsername = $targetUser->username;
$targetUserPermissions = $targetUser->getAttribute('Permissions');

$me = Auth::user();
$myPermissions = $me->getAttribute('Permissions');

$availablePermissionsOptions = [];
for ($i = Permissions::Banned; $i <= $myPermissions; $i++) {
    $availablePermissionsOptions[] = [
        'value' => $i,
        'text' => "(" . $i . "): " . Permissions::toString($i),
        'selected' => $i === $targetUserPermissions,
    ];
}

$hasPatreonBadge = HasPatreonBadge($targetUser);
$hasSupporterRole = $targetUser->roles()->where('name', Role::SUPPORTER)->exists();
$patreonSupporterTier = $hasPatreonBadge ? ($hasSupporterRole ? 2 : 1) : 0;
$patreonSupporterActions = [
    1 => [
        'label' => $patreonSupporterTier === 1 ? 'Remove $1 supporter' : 'Set $1 supporter',
        'confirmation' => $patreonSupporterTier === 1
            ? 'Are you sure you want to remove $1 Patreon supporter status?'
            : 'Are you sure you want to award $1 Patreon supporter status?',
        'class' => $patreonSupporterTier === 1 ? 'btn btn-danger' : 'btn',
    ],
    2 => [
        'label' => $patreonSupporterTier === 2 ? 'Remove $2 supporter' : 'Set $2 supporter',
        'confirmation' => $patreonSupporterTier === 2
            ? 'Are you sure you want to remove $2 Patreon supporter status?'
            : 'Are you sure you want to award $2 Patreon supporter status?',
        'class' => $patreonSupporterTier === 2 ? 'btn btn-danger' : 'btn',
    ],
];
$hasCertifiedLegendBadge = HasCertifiedLegendBadge($targetUser);
$certifiedLegendAction = $hasCertifiedLegendBadge ? 'remove' : 'award';
$trackedStatusAction = $isTargetUserUntracked ? 'track' : 'untrack';
$firstConnectSmell = ConnectWarning::where('username', $targetUsername)->orWhere('username', $targetUser->display_name)->first();
?>

{{-- TODO port to Filament and delete component --}}
<div
    id="moderator-tools-content"
    class="bg-embed hidden border-x border-b border-text-muted py-2 px-4 w-[calc(100%+40px)] -mx-5 -mt-3 sm:-mt-1.5 mb-4"
>
    <table>
        @if ($me->getAttribute('Permissions') >= $targetUserPermissions && $me->username !== $targetUsername)
            <tr>
                <form method="post" action="/request/user/update.php" onsubmit="return confirm('Are you sure you want to update this user?')">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::UpdatePermissions }}">
                    <input type="hidden" name="target" value="{{ $targetUser->display_name }}">
                    
                    <td class="text-right">
                        <button class="btn">Update Account Type</button>
                    </td>
                    <td>
                        <select name="value">
                            @foreach ($availablePermissionsOptions as $option)
                                <option
                                    value="{{ $option['value'] }}"
                                    @if ($option['selected']) selected @endif
                                >
                                    {{ $option['text'] }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </form>
            </tr>
        @endif

        <tr>
            <td class="text-right">
                <div class="flex flex-wrap justify-end gap-2">
                    @foreach ($patreonSupporterActions as $tier => $action)
                        <form method="post" action="/request/user/update.php" onsubmit="return confirm('{{ $action['confirmation'] }}')">
                            @csrf
                            <input type="hidden" name="property" value="{{ UserAction::PatreonBadge }}">
                            <input type="hidden" name="target" value="{{ $targetUser->display_name }}">
                            <input type="hidden" name="value" value="{{ $tier }}" />
                            <button class="{{ $action['class'] }}">{{ $action['label'] }}</button>
                        </form>
                    @endforeach
                </div>
            </td>
            <td>
                {{ match ($patreonSupporterTier) {
                    1 => '$1 Patreon Supporter',
                    2 => '$2 Patreon Supporter',
                    default => 'Not a Patreon Supporter',
                } }}
            </td>
        </tr>

        <tr>
            <td class="text-right">
                <form method="post" action="/request/user/update.php" onsubmit="return confirm('Are you sure you want to {{ $certifiedLegendAction }} Certified Legend status?')">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::LegendBadge }}">
                    <input type="hidden" name="target" value="{{ $targetUser->display_name }}">
                    <input type="hidden" name="value" value="0" />
                    <button class="{{ $hasCertifiedLegendBadge ? 'btn btn-danger' : 'btn' }}">{{ ucfirst($certifiedLegendAction) }} Certified Legend</button>
                </form>
            </td>
            <td>
                {{ $hasCertifiedLegendBadge ? 'Certified Legend' : 'Not Yet Legendary' }}
            </td>
        </tr>

        <tr>
            <td class="text-right">
                <form method="post" action="/request/user/update.php" onsubmit="return confirm('Are you sure you want to {{ $trackedStatusAction }} this user?')">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::TrackedStatus }}">
                    <input type="hidden" name="target" value="{{ $targetUser->display_name }}">
                    <input type="hidden" name="value" value="{{ $isTargetUserUntracked ? 0 : 1 }}" />
                    <button class="btn btn-danger">{{ ucfirst($trackedStatusAction) }} User</button>
                </form>
            </td>
            <td class="w-full">
                {{ $isTargetUserUntracked ? 'Untracked User' : 'Tracked User' }}
                @if ($firstConnectSmell)
                    - <a href="/sentry.php?user={{ $firstConnectSmell->username }}">Connect Smells</a>
                @endif
            </td>
        </tr>

        <tr>
            <td class="text-right">
                <form method="post" action="/request/user/remove-avatar.php" onsubmit="return confirm('Are you sure you want to permanently delete this avatar?')">
                    @csrf
                    <input type="hidden" name="user" value="{{ $targetUsername }}">
                    <button class="btn btn-danger">Remove Avatar</button>
                </form>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <div class="commentscomponent left">
                    <x-comment.list
                        :commentableType="CommentableType::UserModeration"
                        :commentableId="$targetUser->id"
                        :article="$targetUser"
                        :showAll="true"
                    />
                </div>
            </td>
        </tr>
    </table>
</div>
