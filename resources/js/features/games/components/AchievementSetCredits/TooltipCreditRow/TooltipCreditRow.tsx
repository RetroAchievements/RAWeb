import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { FaTrophy } from 'react-icons/fa';
import { route } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface TooltipCreditRowProps {
  credit: App.Platform.Data.UserCredits;

  children?: ReactNode;
  canLinkToUser?: boolean;
  showAchievementCount?: boolean;
  showCreditDate?: boolean;
}

export const TooltipCreditRow: FC<TooltipCreditRowProps> = ({
  children,
  credit,
  canLinkToUser = false,
  showAchievementCount = false,
  showCreditDate = false,
}) => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <p className="flex w-full justify-between gap-2">
      {!credit.isGone && canLinkToUser ? (
        <a
          href={route('user.show', { user: credit.displayName })}
          className="flex items-center gap-1"
        >
          <img src={credit.avatarUrl} alt={credit.displayName} className="size-4 rounded-full" />
          <span>{credit.displayName}</span>
        </a>
      ) : (
        <span className="flex items-center gap-1">
          <img src={credit.avatarUrl} alt={credit.displayName} className="size-4 rounded-full" />
          <span className={cn(credit.isGone ? 'text-neutral-500 line-through' : null)}>
            {credit.displayName}
          </span>
        </span>
      )}

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
