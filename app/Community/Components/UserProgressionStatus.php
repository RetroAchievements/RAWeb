<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Models\System;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserProgressionStatus extends Component
{
    public function __construct(
        public array $systemProgress = [],
        public array $totalCounts = [],
        public ?int $topSystemId = null,
        public int $userHardcorePoints = 0,
        public int $userSoftcorePoints = 0,
    ) {
    }

    public function render(): ?View
    {
        return view('components.user.progression-status.root', [
            'systemProgress' => $this->systemProgress,
            'topSystem' => $this->topSystemId,
            'totalCounts' => $this->totalCounts,
            'userHardcorePoints' => $this->userHardcorePoints,
            'userSoftcorePoints' => $this->userSoftcorePoints,
            'systems' => System::whereIn('ID', array_keys($this->systemProgress))->get()->keyBy('ID'),
        ]);
    }
}
