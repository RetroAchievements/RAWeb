<?php

use App\Models\Game;
use App\Models\User;
use App\Platform\Services\GameUserAchievementsGridService;
use Illuminate\Support\Facades\Blade;

use function Livewire\Volt\{mount, placeholder, state};

// == props
state(['achievementCount']);
state(['gameId'])->locked(); // number
state(['targetUsername'])->locked(); // string

// == state
state(['gameAchievementsWithProgress' => []]);
state(['isLoading' => true]);

// == actions
$loadContent = function() {
    $game = Game::find($this->gameId);
    $user = User::firstWhere('User', $this->targetUsername);

    $service = new GameUserAchievementsGridService();
    $this->gameAchievementsWithProgress = $service->getGameAchievementsWithUserProgress(
        $game,
        $user,
    );
};

// == lifecycle

?>

<div
    x-data="userAchievementsGrid"
    x-intersect="isVisible = true"
    class="relative"
>
    <div x-show="!isLoading" class="absolute w-full top-0 left-0">
        <div 
            class="place-content-center grid grid-cols-[repeat(auto-fill,minmax(52px,52px))] px-0.5 sm:px-4"
        >
            @foreach (($gameAchievementsWithProgress) as $achievement)
                {!!
                    achievementAvatar(
                        $achievement,
                        label: false,
                        iconSize: 48,
                        iconClass: "badgeimglarge {$achievement['BadgeClassNames']}",
                        loading: 'eager',
                    )
                !!}
            @endforeach
        </div>
    </div>

    <div
        x-ref="skeleton"
        class="z-[1] w-full h-full transition-opacity duration-500"
        :class="{ 'opacity-0 pointer-events-none': !isLoading }"
    >
        <x-game-list-item.user-achievements-grid.skeleton
            :$achievementCount
        />
    </div>
</div>

@script
<script>
    Alpine.data('userAchievementsGrid', () => {
        return {
            isLoading: $wire.entangle('isLoading'),
            gameAchievementsWithProgress: $wire.entangle('gameAchievementsWithProgress'),
            isVisible: false,

            init() {
                this.$watch('isVisible', () => {
                    $wire.call('loadContent').then(() => {
                        this.checkImagesLoaded();

                        // Force isLoading to `false` after 1 second. This should only fire
                        // if the user has a slow network connection to the media server.
                        const timeoutId = setTimeout(() => {
                            this.isLoading = false;
                        }, 1000);
                    });
                });
            },

            checkImagesLoaded() {
                const imageEls = document.querySelectorAll('.badgeimglarge');
                let loadedImagesCount = 0;
                const totalImages = imageEls.length;

                imageEls.forEach((img) => {
                    if (img.complete) {
                        loadedImagesCount += 1;
                    } else {
                        img.addEventListener('load', () => {
                            loadedImagesCount += 1;
                            if (loadedImagesCount === totalImages) {
                                this.isLoading = false;
                            }
                        });
                        img.addEventListener('error', () => {
                            loadedImagesCount += 1;
                            if (loadedImagesCount === totalImages) {
                                this.isLoading = false;
                            }
                        });
                    }
                });

                if (loadedImagesCount === totalImages) {
                    this.isLoading = false;
                }
            },
        }
    });
</script>
@endscript
