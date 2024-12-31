import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { FaInfoCircle } from 'react-icons/fa';

import { BaseCard, BaseCardContent, BaseCardHeader } from '@/common/components/+vendor/BaseCard';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import type { TranslatedString } from '@/types/i18next';

interface ActivityStatCardProps {
  children: ReactNode;
  t_label: TranslatedString;

  t_tooltip?: TranslatedString;
}

export const ActivityStatCard: FC<ActivityStatCardProps> = ({ children, t_label, t_tooltip }) => {
  const { t } = useTranslation();

  return (
    <BaseCard>
      <BaseCardHeader className="flex flex-row items-center gap-1 px-4 pb-0 pt-4">
        {t_label}

        {t_tooltip ? (
          <BaseTooltip>
            <BaseTooltipTrigger>
              <FaInfoCircle className="size-4 text-neutral-600" />
              <span className="sr-only">{t('See more info')}</span>
            </BaseTooltipTrigger>

            <BaseTooltipContent sideOffset={12}>
              <span className="max-w-[280px] text-xs">{t_tooltip}</span>
            </BaseTooltipContent>
          </BaseTooltip>
        ) : null}
      </BaseCardHeader>

      <BaseCardContent className="px-4 pb-4">
        <div className="text-xl">{children}</div>
      </BaseCardContent>
    </BaseCard>
  );
};
