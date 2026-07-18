@php
    use App\Support\Shortcode\Shortcode;

    $submissionCount = $notificationItem['count'] ?? 1;
@endphp

@if ($submissionCount === 1)
@php
    $rejectionNotes = $notificationItem['rejectionNotes'] ?? null;
    $sanitizedNotes = $rejectionNotes !== null ? Shortcode::sanitizeForMailMarkdown($rejectionNotes) : null;

    $message = match ($notificationItem['status'] ?? null) {
        'approved' => 'was approved.',
        'rejected' => 'was rejected'
            . (($notificationItem['rejectionReason'] ?? null) ? ': ' . $notificationItem['rejectionReason'] : '')
            . ($sanitizedNotes ? ' - *' . $sanitizedNotes . '*' : '')
            . '.',
        default => 'was reviewed.',
    };
@endphp
Your screenshot submission for [{{ $notificationItem['title'] }}]({{ $notificationItem['link'] }}) {!! $message !!}
@else
@php
    $gameCount = $notificationItem['gameCount'] ?? 1;
    $subject = match (true) {
        $gameCount === 1 && ($notificationItem['title'] ?? null) && ($notificationItem['link'] ?? null)
            => "Your {$submissionCount} screenshot submissions for [{$notificationItem['title']}]({$notificationItem['link']})",
        $gameCount === 1 && ($notificationItem['title'] ?? null)
            => "Your {$submissionCount} screenshot submissions for {$notificationItem['title']}",
        default
            => "Your {$submissionCount} screenshot submissions across {$gameCount} " . Str::plural('game', $gameCount),
    };

    $resultParts = [];
    if (($notificationItem['approvedCount'] ?? 0) > 0) {
        $resultParts[] = "{$notificationItem['approvedCount']} approved";
    }
    if (($notificationItem['rejectedCount'] ?? 0) > 0) {
        $rejectedPart = "{$notificationItem['rejectedCount']} rejected";
        if ($notificationItem['rejectionReasonSummary'] ?? null) {
            $rejectedPart .= " ({$notificationItem['rejectionReasonSummary']})";
        }
        $resultParts[] = $rejectedPart;
    }
    if (($notificationItem['reviewedCount'] ?? 0) > 0) {
        $resultParts[] = "{$notificationItem['reviewedCount']} reviewed";
    }

    $rejectedItems = $notificationItem['rejectedItems'] ?? [];
@endphp
{{ $subject }} were reviewed: {{ implode(', ', $resultParts) }}.
@if (!empty($rejectedItems))

@foreach ($rejectedItems as $rejectedItem)
@php
    $itemReason = $rejectedItem['reason'] ?? null;
    $itemNotes = $rejectedItem['notes'] ?? null;
    $sanitizedItemNotes = ($itemNotes !== null && $itemNotes !== '')
        ? Shortcode::sanitizeForMailMarkdown($itemNotes)
        : null;
@endphp
 - Rejected{{ $itemReason ? " ({$itemReason})" : '' }}{!! $sanitizedItemNotes ? ": {$sanitizedItemNotes}" : '' !!}
@endforeach
@endif
@endif
