import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { useFormatDate } from '@/common/hooks/useFormatDate';

import { AwardIndicator } from '../AwardIndicator';
import { GameAvatar } from '../GameAvatar';

interface GamesListItemProps {
  game: App.Platform.Data.Game;
  playerGame: App.Platform.Data.PlayerGame | null;
}

export const GamesListItem: FC<GamesListItemProps> = ({ game, playerGame }) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  return (
    <div className="flex w-full flex-col gap-x-2 px-2 py-2.5 sm:grid sm:grid-cols-8">
      <div className="flex flex-col gap-y-1 sm:col-span-4">
        <GameAvatar {...game} size={64} showSystemChip={true} />
      </div>

      <div className="flex flex-col justify-center sm:col-span-2">
        <PlayerGameProgressBar
          playerGame={playerGame}
          game={game}
          variant="base"
          width={128}
          className="py-0"
        />

        <p>
          {t('{{earned, number}} of {{total, number}}', {
            earned: playerGame?.achievementsUnlockedHardcore ?? 0,
            total: game.achievementsPublished,
          })}
        </p>
      </div>

      <div className="flex flex-col justify-center sm:col-span-2 sm:items-end">
        {playerGame && playerGame.completedHardcoreAt ? (
          <div className="flex items-center gap-1">
            <AwardIndicator awardKind="mastery" />
            {t('Mastered {{masteredDate}}', {
              masteredDate: formatDate(playerGame.completedHardcoreAt, 'lll'),
            })}
          </div>
        ) : playerGame && playerGame.completedAt ? (
          <div className="flex items-center gap-1">
            <AwardIndicator awardKind="completion" />
            {t('Completed {{completedDate}}', {
              completedDate: formatDate(playerGame.completedAt, 'lll'),
            })}
          </div>
        ) : null}
        {playerGame && playerGame.beatenHardcoreAt ? (
          <div className="flex items-center gap-1">
            <AwardIndicator awardKind="beaten-hardcore" />
            {t('Beaten {{beatenDate}}', {
              beatenDate: formatDate(playerGame.beatenHardcoreAt, 'lll'),
            })}
          </div>
        ) : playerGame && playerGame.beatenAt ? (
          <div className="flex items-center gap-1">
            <AwardIndicator awardKind="beaten-softcore" />
            {t('Beaten (softcore) {{beatenDate}}', {
              beatenDate: formatDate(playerGame.beatenAt, 'lll'),
            })}
          </div>
        ) : null}
      </div>
    </div>
  );
};
