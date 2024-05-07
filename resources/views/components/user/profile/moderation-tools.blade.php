<?php
use App\Community\Enums\ArticleType;
use App\Community\Enums\UserAction;
use App\Enums\Permissions;
?>

@props([
    'targetUser' => null, // User
])

<?php
$isTargetUserUntracked = $targetUser->Untracked;
$targetUsername = $targetUser->User;
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
$hasCertifiedLegendBadge = HasCertifiedLegendBadge($targetUser);
?>

{{-- TODO port to Filament and delete component --}}
<div
    id="moderator-tools-content"
    class="bg-embed hidden border-x border-b border-text-muted py-2 px-4 w-[calc(100%+40px)] -mx-5 -mt-3 sm:-mt-1.5 mb-4"
>
    <table>
        @if ($me->getAttribute('Permissions') >= $targetUserPermissions && $me->User !== $targetUsername)
            <tr>
                <form method="post" action="/request/user/update.php">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::UpdatePermissions }}">
                    <input type="hidden" name="target" value="{{ $targetUsername }}">
                    
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
                <form method="post" action="/request/user/update.php">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::PatreonBadge }}">
                    <input type="hidden" name="target" value="{{ $targetUsername }}">
                    <input type="hidden" name="value" value="0" />
                    <button class="btn">Toggle Patreon Supporter</button>
                </form>
            </td>
            <td>
                {{ $hasPatreonBadge ? 'Patreon Supporter' : 'Not a Patreon Supporter' }}
            </td>
        </tr>

        <tr>
            <td class="text-right">
                <form method="post" action="/request/user/update.php">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::LegendBadge }}">
                    <input type="hidden" name="target" value="{{ $targetUsername }}">
                    <input type="hidden" name="value" value="0" />
                    <button class="btn">Toggle Certified Legend</button>
                </form>
            </td>
            <td>
                {{ $hasCertifiedLegendBadge ? 'Certified Legend' : 'Not Yet Legendary' }}
            </td>
        </tr>

        <tr>
            <td class="text-right">
                <form method="post" action="/request/user/update.php">
                    @csrf
                    <input type="hidden" name="property" value="{{ UserAction::TrackedStatus }}">
                    <input type="hidden" name="target" value="{{ $targetUsername }}">
                    <input type="hidden" name="value" value="{{ $isTargetUserUntracked ? 0 : 1 }}" />
                    <button class="btn btn-danger">Toggle Tracked Status</button>
                </form>
            </td>
            <td class="w-full">
                {{ $isTargetUserUntracked ? 'Untracked User' : 'Tracked User' }}
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
                    <?php
                        $numLogs = getRecentArticleComments(ArticleType::UserModeration, $targetUser->id, $logs);
                        RenderCommentsComponent(
                            $targetUser->User,
                            $numLogs,
                            $logs,
                            $targetUser->id,
                            ArticleType::UserModeration,
                            $myPermissions,
                        );
                    ?>
                </div>
            </td>
        </tr>
    </table>
</div>
