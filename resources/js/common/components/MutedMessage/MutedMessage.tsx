import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { formatDate } from '@/common/utils/l10n/formatDate';

interface MutedMessageProps {
  /** ISO8601 date */
  mutedUntil: string;
}

export const MutedMessage: FC<MutedMessageProps> = ({ mutedUntil }) => {
  const { t } = useTranslation();

  return (
    <div className="bg-embed p-2 text-center">
      <p className="text-text-muted">
        {t('You are muted until {{date}}.', { date: formatDate(mutedUntil, 'll') })}
      </p>
    </div>
  );
};
