import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBookMarked } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';

export const WantToPlayReason: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseChip
      data-testid="want-to-play-reason"
      className="flex gap-1.5 py-1 text-neutral-300 light:text-neutral-900"
    >
      <LuBookMarked className="size-[18px] lg:hidden xl:block" />
      {t('In your backlog')}
    </BaseChip>
  );
};
