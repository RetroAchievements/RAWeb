@props([
    'name' => 'updatesubscription',
    'subjectType' => 0, // SubscriptionSubjectType
    'subjectId' => 0,
    'isSubscribed' => false,
    'resource' => null, // ?string
])

<div>
    <form id="$name" action="/request/user/update-subscription.php" method="post">";
        {!! csrf_field() !!}
        <input type="hidden" name="subject_type" value="$subjectType"/>
        <input type="hidden" name="subject_id" value="$subjectID"/>
        @if ($isSubscribed)
            <input type="hidden" name="operation" value="unsubscribe"/>
            <button class="btn">
                @if ($resouce)
                    Unsubscribe from {{ $resource }}
                @else
                    Unsubscribe
                @endif
            </button>
        @else
            <input type="hidden" name="operation" value="subscribe"/>
            <button class="btn">
                @if ($resouce)
                    Subscribe to {{ $resource }}
                @else
                    Subscribe
                @endif
            </button>
        @endif
    </form>
</div>
