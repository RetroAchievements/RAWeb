<?php

use function Laravel\Folio\{name};

name('rss.index');

?>
<x-app-layout pageTitle="RSS Feeds">
    <h2>RSS</h2>
    <a href="{{ url('rss-news') }}">
        <x-fas-rss-square />
        News
    </a>
</x-app-layout>
