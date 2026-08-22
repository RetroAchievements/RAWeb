import { useTranslation } from 'react-i18next';

import type { TranslatedString } from '@/types/i18next';

type FilterKind = App.Platform.Enums.TicketListFilterKind;

/**
 * Maps the filter kinds and option values the server sends to translated labels.
 */
export function useTicketListFilterLabels() {
  const { t } = useTranslation();

  const kindLabels: Record<FilterKind, TranslatedString> = {
    type: t('Issue type'),
    publishedStatus: t('Publish status'),
    mode: t('Mode'),
    developerType: t('Developer type'),
    developer: t('Developer'),
    reporter: t('Reporter'),
    emulator: t('Emulator'),
  };

  const selfOrOthersLabels = {
    all: t('All'),
    self: t('Self'),
    others: t('Others'),
  };

  const valueLabels: Record<FilterKind, Record<string, TranslatedString>> = {
    type: {
      '0': t('All'),
      '1': t('Triggered at the wrong time'),
      '2': t('Did not trigger'),
    },
    publishedStatus: {
      all: t('All'),
      published: t('Published'),
      unpublished: t('Unpublished'),
    },
    mode: {
      all: t('All'),
      hardcore: t('Hardcore'),
      softcore: t('Casual'),
      unspecified: t('Unspecified'),
    },
    developerType: {
      all: t('All'),
      active: t('Active'),
      junior: t('Junior'),
      inactive: t('Inactive'),
    },
    developer: selfOrOthersLabels,
    reporter: selfOrOthersLabels,
    emulator: {
      all: t('All'),
      unknown: t('Unknown'),
    },
  };

  const statusLabels: Record<App.Platform.Enums.TicketListStatusFilter, TranslatedString> = {
    unresolved: t('Open'),
    request: t('Request'),
    resolved: t('Resolved'),
    closed: t('Closed'),
    quarantined: t('Quarantined'),
    all: t('All'),
  };

  const getFilterKindLabel = (kind: FilterKind): TranslatedString => kindLabels[kind];

  const getFilterValueLabel = (kind: FilterKind, value: string): string =>
    valueLabels[kind][value] ?? value;

  const getStatusValueLabel = (
    value: App.Platform.Enums.TicketListStatusFilter,
  ): TranslatedString => statusLabels[value];

  return { getFilterKindLabel, getFilterValueLabel, getStatusValueLabel };
}
