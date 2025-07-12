import { type FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaTrophy } from 'react-icons/fa';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface TooltipCreditRowProps {
  credit: App.Platform.Data.UserCredits;

  showAchievementCount?: boolean;
  showCreditDate?: boolean;
}

export const TooltipCreditRow: FC<TooltipCreditRowProps> = ({
  credit,
  showAchievementCount = false,
  showCreditDate = false,
}) => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <p className="flex w-full justify-between gap-1">
      <span className="flex items-center gap-1">
        <img src={credit.avatarUrl} className="size-4 rounded-full" />
        <span className={cn(credit.isGone ? 'text-neutral-500 line-through' : null)}>
          {credit.displayName}
        </span>
      </span>

      {showAchievementCount ? (
        <span className="flex items-center text-neutral-500">
          {'('}
          {formatNumber(credit.count)} <FaTrophy className="ml-1" aria-label={t('Achievements')} />
          {')'}
        </span>
      ) : null}

      {showCreditDate ? (
        // Use 'l' for a predictable width across locales.
        <span className="text-neutral-500">{formatDate(credit.dateCredited!, 'l')}</span>
      ) : null}
    </p>
  );
};
