import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

export function useTicketStateLabels(): Record<App.Community.Enums.TicketState, TranslatedString> {
  const { t } = useTranslation();

  return {
    closed: t('Closed'),
    open: t('Open'),
    quarantined: t('Quarantined'),
    request: t('Request'),
    resolved: t('Resolved'),
  };
}
