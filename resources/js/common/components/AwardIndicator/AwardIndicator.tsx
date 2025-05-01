import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface AwardIndicatorProps {
  awardKind: 'mastery' | 'completion' | 'beaten-hardcore' | 'beaten-softcore';

  className?: string;
  title?: TranslatedString;
}

export const AwardIndicator: FC<AwardIndicatorProps> = ({ awardKind, className, title }) => {
  const { t } = useTranslation();

  let safeTitle = title;
  if (!safeTitle) {
    if (awardKind === 'mastery') safeTitle = t('Mastered');
    if (awardKind === 'completion') safeTitle = t('Completed');
    if (awardKind === 'beaten-hardcore') safeTitle = t('Beaten');
    if (awardKind === 'beaten-softcore') safeTitle = t('Beaten (softcore)');
  }

  return (
    <div
      role="img"
      aria-label={safeTitle}
      title={safeTitle}
      className={cn(
        'h-2 w-2 rounded-full',

        awardKind === 'mastery' ? 'bg-[gold] light:bg-yellow-600' : null,
        awardKind === 'completion' ? 'border border-yellow-600' : null,
        awardKind === 'beaten-hardcore' ? 'bg-zinc-300' : null,
        awardKind === 'beaten-softcore' ? 'border border-zinc-400' : null,

        className,
      )}
    />
  );
};
