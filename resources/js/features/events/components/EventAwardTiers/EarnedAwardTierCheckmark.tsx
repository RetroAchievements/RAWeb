import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { cn } from '@/common/utils/cn';

interface EarnedAwardTierCheckmarkProps {
  earnedAt: string | null;
  hasLink: boolean;
}

export const EarnedAwardTierCheckmark: FC<EarnedAwardTierCheckmarkProps> = ({
  earnedAt,
  hasLink,
}) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const checkmark = (
    <div
      data-testid="award-earned-checkmark"
      className={cn(
        'mr-1 flex size-6 items-center justify-center rounded-full bg-embed',
        'light:bg-neutral-200 light:text-neutral-700',
        hasLink && 'transition group-hover:text-link-hover',
      )}
    >
      <LuCheck className="size-4" />
    </div>
  );

  if (!earnedAt) {
    return checkmark;
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>{checkmark}</BaseTooltipTrigger>

      <BaseTooltipContent>
        {t('Awarded {{awardedDate}}', {
          awardedDate: formatDate(earnedAt, 'lll'),
        })}
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
