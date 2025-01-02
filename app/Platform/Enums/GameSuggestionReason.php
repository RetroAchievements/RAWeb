<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameSuggestionReason: string
{
    case CommonPlayers = 'common-players';
    case Random = 'random';
    case Revised = 'revised';
    case SharedAuthor = 'shared-author';
    case SharedHub = 'shared-hub';
    case SimilarGame = 'similar-game';
    case WantToPlay = 'want-to-play';
}
