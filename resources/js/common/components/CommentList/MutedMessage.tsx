import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { formatDate } from '@/common/utils/l10n/formatDate';

interface MutedMessageProps {
  /** ISO8601 date */
  mutedUntil: string;
}

export const MutedMessage: FC<MutedMessageProps> = ({ mutedUntil }) => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="bg-embed p-2 text-center">
      <p className="text-text-muted">
        {t('You are muted until :date.', { date: formatDate(mutedUntil, 'll') })}
      </p>
    </div>
  );
};
