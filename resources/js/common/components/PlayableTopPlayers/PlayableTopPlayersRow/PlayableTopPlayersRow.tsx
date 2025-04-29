import type { FC } from 'react';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { AwardIndicator } from '../../AwardIndicator';
import type { TopPlayersListKind } from '../models';

interface PlayableTopPlayersRowProps {
  awardKind: 'mastery' | 'beaten-hardcore' | null;
  listKind: TopPlayersListKind;
  numMasters: number;
  player: App.Platform.Data.GameTopAchiever;
  playerIndex: number;

  calculatedRank?: number;
}

export const PlayableTopPlayersRow: FC<PlayableTopPlayersRowProps> = ({
  awardKind,
  calculatedRank,
  listKind,
  numMasters,
  player,
  playerIndex,
}) => {
  const { auth } = usePageProps();

  const getRowNumber = (): number => {
    // For latest masters, we always use the reversed index.
    if (listKind === 'latest-masters') {
      return numMasters - playerIndex;
    }

    // For other list types, use the calculated rank (which accounts for ties).
    return calculatedRank as number;
  };

  const isMe = player.userDisplayName === auth?.user?.displayName;

  return (
    <BaseTableRow
      key={`top-players-${player.userDisplayName}`}
      className={cn('last:rounded-b-lg [&>td]:py-[6px]', isMe ? 'outline outline-text' : null)}
    >
      <BaseTableCell className="text-right">{getRowNumber()}</BaseTableCell>

      <BaseTableCell>
        <UserAvatar
          avatarUrl={player.userAvatarUrl}
          displayName={player.userDisplayName}
          size={16}
        />
      </BaseTableCell>

      {listKind === 'latest-masters' ? (
        <BaseTableCell className="smalldate">
          {formatDate(player.lastUnlockHardcoreAt, 'll')}
        </BaseTableCell>
      ) : (
        <BaseTableCell>
          <span className="flex items-center justify-end gap-1.5">
            {awardKind ? <AwardIndicator awardKind={awardKind} /> : null}
            {player.pointsHardcore}
          </span>
        </BaseTableCell>
      )}
    </BaseTableRow>
  );
};
