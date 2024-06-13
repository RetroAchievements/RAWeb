@props([
    'templateKind' => null, // 'manual-unlock' | 'writing-error' | 'misclassification' | 'unwelcome-concept' | 'achievement-issue' | null
])

<div class="mb-6">
    <x-alert title="Note">
        @switch ($templateKind)
            @case('manual-unlock')
                <div class="leading-5">
                    <p>Please provide as much evidence as possible in your manual unlock request.</p>
                    <p>The person reviewing the request will almost always want either a video or screenshot.</p>
                </div>
                @break

            @case('unwelcome-concept')
                <div class="leading-5">
                    <p>
                        Please follow the template below. If you don't, your request will be ignored.
                    </p>

                    <p>
                        When in doubt,
                        <a href="https://docs.retroachievements.org/guidelines/content/unwelcome-concepts.html" target="_blank">
                            consult the docs.
                        </a>
                    </p>
                </div>
                @break

            @default
                <p>Please provide as much information as possible about the issue.</p>
        @endswitch
    </x-alert>
</div>
