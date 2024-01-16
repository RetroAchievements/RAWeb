<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Site\Models\StaticData;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TotalPointsEarned extends Component
{
    public int $totalPointsEarned = 0;

    public function mount(): void
    {
        $this->totalPointsEarned = $this->getTotalPointsEarned();
    }

    public function render(): View
    {
        $this->totalPointsEarned = $this->getTotalPointsEarned();
        $this->dispatch('updatePoints', $this->totalPointsEarned);

        return view(
            'community.components.global-statistics.total-points-earned', [
                'totalPointsEarned' => $this->totalPointsEarned,
            ]
        );
    }

    public function getTotalPointsEarned(): int
    {
        $totalPointsEarned = 0;

        $dbStaticData = StaticData::first();
        if ($dbStaticData !== null) {
            $totalPointsEarned = $dbStaticData['TotalPointsEarned'] ?? 0;
        }

        return $totalPointsEarned;
    }
}
