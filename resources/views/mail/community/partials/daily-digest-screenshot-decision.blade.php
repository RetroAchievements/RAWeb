@php
    $submissionCount = $notificationItem['count'] ?? 1;
@endphp

@if ($submissionCount === 1)
@php
    $message = match ($notificationItem['status'] ?? null) {
        'approved' => 'was approved.',
        'rejected' => 'was rejected'
            . (($notificationItem['rejectionReason'] ?? null) ? ': ' . $notificationItem['rejectionReason'] : '')
            . '.',
        default => 'was reviewed.',
    };
@endphp
Your screenshot submission for [{{ $notificationItem['title'] }}]({{ $notificationItem['link'] }}) {{ $message }}
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
@endphp
{{ $subject }} were reviewed: {{ implode(', ', $resultParts) }}.
@endif
