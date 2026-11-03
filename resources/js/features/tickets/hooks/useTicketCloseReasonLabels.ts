import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

import type { TicketCloseReason } from '../models';

export function useTicketCloseReasonLabels(): Record<TicketCloseReason, TranslatedString> {
  const { t } = useTranslation();

  return {
    'already-fixed': t('Already fixed'),
    'closed-mistaken': t('Mistaken report'),
    'closed-other': t('Another reason'),
    'emulator-problem': t('Emulator or core problem'),
    'manual-unlock-request': t('Manual unlock request'),
    network: t('Network problems'),
    'not-enough-info': t('Not enough information'),
    'unable-to-debug': t('Unable to debug (no toolkit support)'),
    'unable-to-reproduce': t('Unable to reproduce'),
    'wrong-rom': t('Wrong ROM'),
  };
}
