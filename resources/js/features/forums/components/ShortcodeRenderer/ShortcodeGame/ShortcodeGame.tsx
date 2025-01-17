import { useAtom } from 'jotai';
import type { FC } from 'react';

import { GameAvatar } from '@/common/components/GameAvatar';
import { persistedGamesAtom } from '@/features/forums/state/forum.atoms';

interface ShortcodeGameProps {
  gameId: number;
}

export const ShortcodeGame: FC<ShortcodeGameProps> = ({ gameId }) => {
  const [persistedGames] = useAtom(persistedGamesAtom);

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
