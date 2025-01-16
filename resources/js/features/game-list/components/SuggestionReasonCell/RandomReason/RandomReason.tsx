import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuDices } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';

export const RandomReason: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseChip
      data-testid="random-reason"
      className="flex gap-1.5 py-1 text-neutral-300 light:text-neutral-900"
    >
      <LuDices className="size-[18px]" />
      <span className="inline xl:hidden">{t('Random')}</span>
      <span className="hidden xl:inline">{t('Randomly selected')}</span>
    </BaseChip>
  );
};
