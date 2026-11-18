import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

export function useTicketSignalVerdictLabels(): Record<
  App.Community.Enums.TicketSignalVerdict,
  TranslatedString
> {
  const { t } = useTranslation();

  return {
    'probably-not-defect': t('Probably not a bug'),
    'probably-defect': t('Probably a real bug'),
  };
}
