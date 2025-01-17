import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuRefreshCw } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';

export const RevisedReason: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseChip
      data-testid="revised-reason"
      className="flex gap-1.5 py-1 text-neutral-300 light:text-neutral-900"
    >
      <LuRefreshCw className="size-[18px] lg:hidden xl:block" />
      {t('Revised')}
    </BaseChip>
  );
};
