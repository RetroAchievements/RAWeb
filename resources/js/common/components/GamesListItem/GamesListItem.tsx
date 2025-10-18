import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { AwardIndicator } from '../AwardIndicator';
import { GameAvatar } from '../GameAvatar';

interface GamesListItemProps {
  game: App.Platform.Data.Game;
  index: number;
  playerGame: App.Platform.Data.PlayerGame | null;
}

export const GamesListItem: FC<GamesListItemProps> = ({ game, playerGame }) => {
  const { t } = useTranslation();

  return (
    <div className="flex w-full flex-col gap-x-2 sm:flex-row sm:items-center sm:justify-between">
      <div className="flex flex-col gap-y-1 md:mt-1">
        <GameAvatar {...game} size={64} showSystemChip={true} />
      </div>

      <div className="justify-end">
        <PlayerGameProgressBar playerGame={playerGame} game={game} variant="base" width={128} />

        <p>
          {t('{{earned, number}} of {{total, number}}', {
            earned: playerGame?.achievementsUnlockedHardcore ?? 0,
            total: game.achievementsPublished,
          })}
        </p>
      </div>

      <div>
        {playerGame && playerGame.completedHardcoreAt ? (
          <div className="flex flex-col justify-end">
            <AwardIndicator awardKind="mastery" />
            {t('Mastered {{masteredDate}}', {
              masteredDate: formatDate(playerGame.completedHardcoreAt, 'lll'),
            })}
          </div>
        ) : playerGame && playerGame.completedAt ? (
          <div className="flex flex-col justify-end">
            <AwardIndicator awardKind="completion" />
            {t('Completed {{completedDate}}', {
              completedDate: formatDate(playerGame.completedAt, 'lll'),
            })}
          </div>
        ) : null}
        {playerGame && playerGame.beatenHardcoreAt ? (
          <div className="flex flex-col justify-end">
            <AwardIndicator awardKind="beaten-hardcore" />
            {t('Beaten {{beatenDate}}', {
              beatenDate: formatDate(playerGame.beatenHardcoreAt, 'lll'),
            })}
          </div>
        ) : playerGame && playerGame.beatenAt ? (
          <div className="flex flex-col justify-end">
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
