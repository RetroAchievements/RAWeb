import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuGamepad2 } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { GameAvatar } from '@/common/components/GameAvatar';

interface SimilarGameReasonProps {
  relatedGame: App.Platform.Data.Game;
}

export const SimilarGameReason: FC<SimilarGameReasonProps> = ({ relatedGame }) => {
  const { t } = useTranslation();

  return (
    <BaseChip data-testid="similar-game-reason" className="flex gap-1.5 py-1 text-neutral-300">
      <LuGamepad2 className="size-[18px] lg:hidden xl:block" />

      {t('Similar to')}
      <GameAvatar {...relatedGame} showLabel={false} size={24} wrapperClassName="inline-block" />
    </BaseChip>
  );
};
