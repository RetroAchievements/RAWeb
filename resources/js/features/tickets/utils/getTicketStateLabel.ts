import type { TFunction } from 'i18next';

import type { TranslatedString } from '@/types/i18next';

export function getTicketStateLabel(
  state: App.Community.Enums.TicketState,
  t: TFunction,
): TranslatedString {
  const labels: Record<App.Community.Enums.TicketState, TranslatedString> = {
    closed: t('Closed'),
    open: t('Open'),
    quarantined: t('Quarantined'),
    request: t('Request'),
    resolved: t('Resolved'),
  };

  return labels[state];
}
