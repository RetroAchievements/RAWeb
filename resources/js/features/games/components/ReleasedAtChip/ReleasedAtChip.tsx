import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCalendar } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';

interface ReleasedAtChipProps {
  game: App.Platform.Data.Game;
}

export const ReleasedAtChip: FC<ReleasedAtChipProps> = ({ game }) => {
  const { t } = useTranslation();

  if (!game.releasedAt) {
    return null;
  }

  return (
    <BaseChip>
      <LuCalendar className="mr-0.5 size-4" />
      <span>{t('Released:')}</span>
      <span>{formatGameReleasedAt(game.releasedAt, game.releasedAtGranularity)}</span>
    </BaseChip>
  );
};
