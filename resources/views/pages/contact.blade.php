<?php

use function Laravel\Folio\{name};

name('contact');

?>
<x-app-layout :page-title="__('Contact Us')">
    <div class="lg:grid grid-cols-2 gap-5">
        <div>
            <x-section>
                <h2>Admins and Moderators</h2>

                <p class="mb-0">
                    <a href='{{ route("message.create") }}?to=RAdmin'>Send a message to RAdmin</a> for:
                </p>

                <ul class="mb-3 list-disc list-inside">
                    <li>Reporting offensive behavior.</li>
                    <li>Reporting copyrighted material.</li>
                    <li>Reporting cheating to be investigated.</li>
                    <li>Requesting to be untracked.</li>
                </ul>
            </x-section>

            <x-section>
                <h2>Developer Compliance</h2>

                <p class="mb-0">
                    <a href='{{ route("message.create") }}?to=DevCompliance'>Send a message to DevCompliance</a> for:
                </p>

                <ul class="mb-3 list-disc list-inside">
                    <li>Requesting set approval or early set release.</li>
                    <li>Reporting achievements or sets with unwelcome concepts.</li>
                    <li>Reporting sets failing to cover basic progression.</li>
                </ul>
            </x-section>

            <x-section>
                <h2>Quality Assurance</h2>

                <p class="mb-0">
                    <a href='{{ route("message.create") }}?to=QATeam'>Send a message to QATeam</a> for:
                </p>

                <ul class="mb-3 list-disc list-inside">
                    <li>Reporting a broken set, leaderboard, or rich presence.</li>
                    <li>Reporting achievements with grammatical mistakes.</li>
                    <li>Requesting a set be playtested.</li>
                    <li>Hash compatibility questions.</li>
                    <li>Hub organizational questions.</li>
                    <li>Getting involved in a QA sub-team.</li>
                </ul>
            </x-section>

            <x-section>
                <h2>DevQuest</h2>

                <p class="mb-3">
                    <a href='{{ route("message.create") }}?to=DevQuest'>Send a message to DevQuest</a> for
                    submissions, questions, ideas, or reporting issues related to
                    <a href='/game/5686'>DevQuest</a>.
                </p>
            </x-section>
        </div>

        <div>
            <x-section>
                <h2>Cheating Reports</h2>

                <p class="mb-3">
                    <a href='{{ route("message.create") }}?to=RACheats'>Send a message to RACheats</a>
                    if you believe someone is in violation of our
                    <a href="https://docs.retroachievements.org/Global-Leaderboard-and-Achievement-Hunting-Rules/#not-allowed">Global Leaderboard and Achievement Hunting Rules</a>.
                </p>

                <p>
                    Please include as much evidence as possible to support your claim. This could
                    include screenshots, videos, links to suspicious profiles, or any other relevant
                    information that demonstrates the alleged violation. Describe each piece of evidence in
                    detail, explaining why it suggests a violation of the rules. The more comprehensive and clear
                    your submission, the more efficiently we can evaluate and address the issue.
                </p>
            </x-section>

            <x-section>
                <h2>RANews</h2>

                <p class="mb-0">
                    <a href='{{ route("message.create") }}?to=RANews'>Send a message to RANews</a> for:
                </p>

                <ul class="mb-3 list-disc list-inside">
                    <li>Submitting a Play This Set, Wish This Set, or RAdvantage entry.</li>
                    <li>Submitting a retrogaming article.</li>
                    <li>Proposing a new article idea.</li>
                    <li>Getting involved with RANews.</li>
                </ul>
            </x-section>

            <x-section>
                <h2>RAEvents</h2>

                <p class="mb-3">
                    <a href='{{ route("message.create") }}?to=RAEvents'>Send a message to RAEvents</a> for
                    submissions, questions, ideas, or reporting issues related to
                    <a href='/game/3105'>community events</a>.
                </p>

                <p class="mb-3">
                    <a href='{{ route("message.create") }}?to=TheUnwanted'>Send a message to TheUnwanted</a> for
                    submissions, questions, ideas, or reporting issues specifically related to
                    <a href='/game/4271'>The Unwanted</a>.
                </p>
            </x-section>
        </div>
    </div>
</x-app-layout>
