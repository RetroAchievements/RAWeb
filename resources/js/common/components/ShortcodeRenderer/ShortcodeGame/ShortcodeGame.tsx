import { useAtomValue } from 'jotai';
import type { FC } from 'react';

import { persistedGamesAtom } from '../../../state/shortcode.atoms';
import { GameAvatar } from '../../GameAvatar';

interface ShortcodeGameProps {
  gameId: number;
}

export const ShortcodeGame: FC<ShortcodeGameProps> = ({ gameId }) => {
  const persistedGames = useAtomValue(persistedGamesAtom);

  const foundGame = persistedGames?.find((game) => game.id === gameId);

  if (!foundGame) {
    return null;
  }

  return (
    <span data-testid="game-embed" className="inline">
      <GameAvatar {...foundGame} showSystemInTitle={true} size={24} variant="inline" />
    </span>
  );
};
