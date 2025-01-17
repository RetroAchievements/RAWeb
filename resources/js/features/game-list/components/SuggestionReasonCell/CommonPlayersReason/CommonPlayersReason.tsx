import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleDot, LuTrophy } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { GameAvatar } from '@/common/components/GameAvatar';

interface CommonPlayersReasonProps {
  relatedGame: App.Platform.Data.Game | null;
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

      {relatedGame ? (
        <>
          {sourceGameKind === 'beaten' ? t('Beaten by players of') : t('Mastered by players of')}
          <GameAvatar
            {...relatedGame}
            showLabel={false}
            size={24}
            wrapperClassName="inline-block"
          />
        </>
      ) : sourceGameKind === 'beaten' ? (
        t('Also beaten by same players')
      ) : (
        t('Also mastered by same players')
      )}
    </BaseChip>
  );
};
