import type { FC } from 'react';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { UserAvatar } from '@/common/components/UserAvatar';
import { formatDate } from '@/common/utils/l10n/formatDate';

import type { TopPlayersListKind } from '../models/top-players-list-kind.model';

interface TopEventPlayersRowProps {
  listKind: TopPlayersListKind;
  numMasters: number;
  player: App.Platform.Data.GameTopAchiever;
  playerIndex: number;

  calculatedRank?: number;
}

export const TopEventPlayersRow: FC<TopEventPlayersRowProps> = ({
  calculatedRank,
  listKind,
  numMasters,
  player,
  playerIndex,
}) => {
  const getRowNumber = (): number => {
    // For latest masters, we always use the reversed index.
    if (listKind === 'latest-masters') {
      return numMasters - playerIndex;
    }

    // For other list types, use the calculated rank (which accounts for ties).
    return calculatedRank as number;
  };

  return (
    <BaseTableRow
      key={`top-players-${player.userId}`}
      className="last:rounded-b-lg [&>td]:py-[6px]"
    >
      <BaseTableCell className="text-right">{getRowNumber()}</BaseTableCell>

      <BaseTableCell>
        <UserAvatar {...player.user!} size={16} />
      </BaseTableCell>

      {listKind === 'latest-masters' ? (
        <BaseTableCell className="smalldate">
          {formatDate(player.lastUnlockHardcoreAt, 'll')}
        </BaseTableCell>
      ) : (
        <BaseTableCell className="text-right">{player.pointsHardcore}</BaseTableCell>
      )}
    </BaseTableRow>
  );
};
