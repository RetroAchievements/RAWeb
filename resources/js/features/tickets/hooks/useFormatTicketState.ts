import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

import { useTicketResolutionLabels } from './useTicketResolutionLabels';
import { useTicketStateLabels } from './useTicketStateLabels';

export function useFormatTicketState() {
  const { t } = useTranslation();

  const resolutionLabels = useTicketResolutionLabels();
  const stateLabels = useTicketStateLabels();

  const formatTicketState = (
    ticketState: App.Community.Enums.TicketState,
    ticketResolution: App.Community.Enums.TicketResolution | null | undefined,
  ): TranslatedString => {
    const baseStateLabel = stateLabels[ticketState];

    if (!ticketResolution) {
      return baseStateLabel;
    }

    return t('{{ticketState}}: {{ticketResolution}}', {
      ticketState: baseStateLabel,
      ticketResolution: resolutionLabels[ticketResolution],
    });
  };

  return { formatTicketState };
}
