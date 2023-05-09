<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use Exception;
use Livewire\Component;

class ActivePlayers extends Component
{
    public $activePlayers = [];
    public $hasError = false;

    public function mount()
    {
        $this->updateActivePlayers();
    }

    public function updateActivePlayers()
    {
        try {
            $activePlayers = getCachedLatestRichPresenceUpdates();

            foreach ($activePlayers as &$player) {
                $player['userAvatarHtml'] = userAvatar($player['User'], iconSize: 32, label: false);
                $player['gameAvatarHtml'] = gameAvatar(
                    [
                        'ID' => $player['GameID'],
                        'ImageIcon' => $player['GameIcon'],
                        'GameTitle' => $player['GameTitle'],
                        'ConsoleName' => $player['ConsoleName'],
                    ],
                    iconSize: 32,
                    label: false,
                );
            }

            $this->activePlayers = $activePlayers;
        } catch (Exception $e) {
            $this->hasError = true;
        }
    }

    public function render()
    {
        return view('livewire.active-players');
    }
}
