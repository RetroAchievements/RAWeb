import type { FC } from 'react';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { UserAvatar } from '@/common/components/UserAvatar';
import { formatDate } from '@/common/utils/l10n/formatDate';

import type { TopPlayersListKind } from '../top-players-list-kind.model';

interface TopEventPlayersRowProps {
  listKind: TopPlayersListKind;
  numMasters: number;
  player: App.Platform.Data.GameTopAchiever;
  playerIndex: number;
}

export const TopEventPlayersRow: FC<TopEventPlayersRowProps> = ({
  listKind,
  numMasters,
  player,
  playerIndex,
}) => {
  const getRowNumber = (index: number): number => {
    if (listKind === 'latest-masters') {
      return numMasters - index;
    }

    return index + 1;
  };

  return (
    <BaseTableRow
      key={`top-players-${player.userId}`}
      className="last:rounded-b-lg [&>td]:py-[6px]"
    >
      <BaseTableCell className="text-right">{getRowNumber(playerIndex)}</BaseTableCell>

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
