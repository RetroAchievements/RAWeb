<form action="{{ $action ?? null }}" method="post" enctype="multipart/form-data">
    @csrf
    @method($method ?? 'post')
    {{ $slot }}
</form>
