<x-mail::message>
The following conversations that you have participated in were updated recently:

@foreach ($notificationItems as $notificationItem)
  <span>*</span>
  @switch ($notificationItem['type'])
    @case('ForumTopic')
        <span>Forum Topic</span>
        @break
    @default
        <span>{{ $notificationItem['type'] }}</span>
        @break
  @endswitch
  <a href="{{ $notificationItem['link'] }}">{{ $notificationItem['title'] }}</a>
  @switch ($notificationItem['type'])
    @case('ForumTopic')
        <span>({{ $notificationItem['count'] }} new {{ Str::plural('post', $notificationItem['count']) }})</span>
        @break
    @default
        <span>({{ $notificationItem['count'] }} new {{ Str::plural('comment', $notificationItem['count']) }})</span>
        @break
  @endswitch
@endforeach

</x-mail::message>
