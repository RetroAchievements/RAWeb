{{--
    This stuff shouldn't be in the Livewire component because
    the template is sent to the browser on each re-render.
--}}

<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/1.7.0/countUp.min.js"></script>

<script>
document.addEventListener('livewire:initialized', () => {
    const counter = new CountUp('points-counter', {{ $totalPointsEarned }});
    Livewire.on('updatePoints', (totalPoints) => {
        counter.update(totalPoints);
    });
});
</script>
