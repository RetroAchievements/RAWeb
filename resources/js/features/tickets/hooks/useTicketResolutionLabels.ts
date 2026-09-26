import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

/**
 * The filter options and the table column both show resolutions, so they
 * share one label map to keep the wording the same in both places.
 */
export function useTicketResolutionLabels(): Record<
  App.Community.Enums.TicketResolution,
  TranslatedString
> {
  const { t } = useTranslation();

  return {
    fixed: t('Fixed'),
    mistaken_report: t('Mistaken report'),
    not_enough_information: t('Not enough information'),
    wrong_rom: t('Wrong ROM'),
    network_problems: t('Network problems'),
    unable_to_reproduce: t('Unable to reproduce'),
    unable_to_debug: t('Unable to debug'),
    demoted: t('Demoted'),
    other: t('Other'),
  };
}
