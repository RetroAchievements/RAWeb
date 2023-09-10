@props([
    'totalUserPoints' => 0,
    'totalBeatenSoftcoreCount' => 0,
    'totalBeatenHardcoreCount' => 0,
])

<?php
$currentUser = Auth::user();
$viewedUser = request()->route('user');

$expectedMaybeBeatenAwards = $totalUserPoints > 5000 ? $totalUserPoints / 5000 : 0;
$totalBeatenAwards = $totalBeatenSoftcoreCount + $totalBeatenHardcoreCount;

$canRender =
    $currentUser
    && $currentUser->User === $viewedUser
    && $expectedMaybeBeatenAwards > 0
    && $expectedMaybeBeatenAwards > $totalBeatenAwards;
?>

@if ($canRender)
    <form method="post" action="/request/user/recalculate-score.php">
        {{ csrf_field()}}
        <input type="hidden" name="user" value="{{ $currentUser->User }}">
        <p class="mb-4 text-2xs">
            We've detected you may be missing some beaten game awards.
            <button class="text-link hover:text-link-hover">Click here</button>
            to do an awards recalculation.
        </p>
    </form>
@endif