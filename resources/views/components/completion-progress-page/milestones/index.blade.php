@props([
    'milestones' => [],
    'isFiltering' => false,
    'isMe' => false,
    'user' => null, // ?User
])

<div class="sticky">
    <h2 class="text-h3">Milestones</h2>

    @if (count($milestones) === 0)
        {{ $isMe ? "You don't " : $user->display_name . " doesn't " }}
        have any milestones
        {{ $isFiltering ? "matching your current filter criteria." : "yet." }}
    @else
        <table>
            <tbody>
                @foreach ($milestones as $milestone)
                    <x-completion-progress-page.milestones.table-row :milestone="$milestone" />
                @endforeach
            </tbody>
        </table>
    @endif
</div>
