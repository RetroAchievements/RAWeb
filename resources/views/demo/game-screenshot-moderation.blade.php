{{-- TODO remove after the Filament moderation UI is in place --}}

<x-demo-layout pageTitle="Screenshot Moderation Demo">
    <div class="flex flex-col gap-y-5">
        <div>
            <h1>Screenshot Moderation Demo</h1>
            <p class="text-xs">
                {{ $pendingScreenshots->count() }} pending screenshots
            </p>
        </div>

        @forelse ($pendingScreenshots as $screenshot)
            <div class="border-b border-neutral-700 pb-4 last:border-b-0 last:pb-0">
                <div class="flex gap-4">
                    <div class="flex-none" style="width: 180px;">
                        <a href="{{ $screenshot->media->getUrl() }}" target="_blank" rel="noreferrer">
                            <img
                                alt="Screenshot #{{ $screenshot->id }}"
                                class="rounded border border-neutral-700"
                                src="{{ $screenshot->media->getUrl() }}"
                                style="display: block; width: 180px; max-width: 100%; height: auto;"
                            >
                        </a>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="mb-3">
                            <div class="text-lg font-semibold leading-tight">
                                <a href="{{ route('game.show', $screenshot->game) }}" target="_blank" rel="noreferrer">
                                    {{ $screenshot->game->title }}
                                </a>
                            </div>
                        </div>

                        <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-neutral-500">Type</dt>
                                <dd>{{ $screenshot->type->label() }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500">Resolution</dt>
                                <dd>{{ $screenshot->width }}x{{ $screenshot->height }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500">Uploader</dt>
                                <dd>{{ $screenshot->capturedBy?->display_name ?? 'Unknown' }}</dd>
                            </div>
                            <div>
                                <dt class="text-neutral-500">Created</dt>
                                <dd>{{ $screenshot->created_at?->toDateTimeString() }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex flex-col gap-2">
                            <form
                                action="{{ route('demo.game-screenshot-moderation.approve', $screenshot) }}"
                                method="POST"
                            >
                                @csrf
                                <button class="btn" type="submit">Approve</button>
                            </form>

                            <form
                                action="{{ route('demo.game-screenshot-moderation.reject', $screenshot) }}"
                                class="flex flex-col gap-2 md:flex-row md:items-end"
                                method="POST"
                            >
                                @csrf

                                <div>
                                    <label class="block text-xs text-neutral-500 mb-1" for="reason_{{ $screenshot->id }}">Reject reason</label>
                                    <select class="form-control" id="reason_{{ $screenshot->id }}" name="reason" required style="min-width: 190px;">
                                        @foreach ($rejectionReasons as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="flex-1">
                                    <label class="block text-xs text-neutral-500 mb-1" for="notes_{{ $screenshot->id }}">Notes</label>
                                    <input
                                        class="form-control w-full"
                                        id="notes_{{ $screenshot->id }}"
                                        maxlength="1000"
                                        name="notes"
                                        placeholder="Optional"
                                        type="text"
                                    >
                                </div>

                                <div>
                                    <button class="btn btn-danger" type="submit">Reject</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded border border-dashed border-neutral-700 p-8 text-center text-neutral-500">
                No pending screenshots found.
            </div>
        @endforelse
    </div>
</x-demo-layout>
