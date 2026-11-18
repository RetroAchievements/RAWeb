import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

export function useTicketTypeLabels(): Record<App.Community.Enums.TicketType, TranslatedString> {
  const { t } = useTranslation();

  return {
    did_not_cancel: t('Did not cancel'),
    did_not_start: t('Did not start'),
    did_not_submit: t('Did not submit'),
    did_not_trigger: t('Did not trigger'),
    submitted_wrong_value: t('Submitted wrong value'),
    triggered_at_wrong_time: t('Triggered at the wrong time'),
  };
}
