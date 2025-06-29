import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { FaTrophy } from 'react-icons/fa';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface TooltipCreditRowProps {
  credit: App.Platform.Data.UserCredits;

  children?: ReactNode;
  showAchievementCount?: boolean;
  showCreditDate?: boolean;
}

export const TooltipCreditRow: FC<TooltipCreditRowProps> = ({
  children,
  credit,
  showAchievementCount = false,
  showCreditDate = false,
}) => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <p className="flex w-full justify-between gap-2">
      <span className="flex items-center gap-1">
        <img src={credit.avatarUrl} className="size-4 rounded-full" />
        <span>{credit.displayName}</span>
      </span>

      {children ? <span className="text-neutral-500">{children}</span> : null}

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
