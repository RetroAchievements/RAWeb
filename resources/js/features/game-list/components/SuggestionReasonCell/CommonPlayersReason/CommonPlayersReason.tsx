import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleDot, LuTrophy } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { GameAvatar } from '@/common/components/GameAvatar';

interface CommonPlayersReasonProps {
  relatedGame: App.Platform.Data.Game;
  sourceGameKind: App.Platform.Services.GameSuggestions.Enums.SourceGameKind;
}

export const CommonPlayersReason: FC<CommonPlayersReasonProps> = ({
  relatedGame,
  sourceGameKind,
}) => {
  const { t } = useTranslation();

  const IconComponent = sourceGameKind === 'beaten' ? LuCircleDot : LuTrophy;

  return (
    <BaseChip
      data-testid="common-players-reason"
      className="flex gap-1.5 whitespace-nowrap py-1 text-neutral-300 light:text-neutral-900"
    >
      <IconComponent className="size-[18px] lg:hidden xl:block" />

      <span className="inline xl:hidden">
        {sourceGameKind === 'beaten' ? t('Similar beats') : t('Similar masteries')}
      </span>
      <span className="hidden xl:inline">
        {sourceGameKind === 'beaten' ? t('Beaten by players of') : t('Mastered by players of')}
      </span>
      <GameAvatar {...relatedGame} showLabel={false} size={24} wrapperClassName="inline-block" />
    </BaseChip>
  );
};
