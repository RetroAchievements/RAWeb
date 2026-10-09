import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

import { getTicketStateLabel } from '../utils/getTicketStateLabel';
import { useTicketResolutionLabels } from './useTicketResolutionLabels';

export function useTicketStateLabel() {
  const { t } = useTranslation();

  const resolutionLabels = useTicketResolutionLabels();

  const buildTicketStateLabel = (
    ticketState: App.Community.Enums.TicketState,
    ticketResolution: App.Community.Enums.TicketResolution | null | undefined,
  ): TranslatedString => {
    const baseStateLabel = getTicketStateLabel(ticketState, t);

    if (!ticketResolution) {
      return baseStateLabel;
    }

    return t('{{ticketState}}: {{ticketResolution}}', {
      ticketState: baseStateLabel,
      ticketResolution: resolutionLabels[ticketResolution],
    });
  };

  return { buildTicketStateLabel };
}
