import { useTranslation } from 'react-i18next';

import type { TicketListFilterProperty } from '../models';
import { useTicketListFilterLabels } from './useTicketListFilterLabels';

const STATUS_GLYPH_STATES: Record<
  App.Platform.Enums.TicketListStatusFilter,
  App.Community.Enums.TicketState | undefined
> = {
  unresolved: 'open',
  request: 'request',
  resolved: 'resolved',
  closed: 'closed',
  quarantined: 'quarantined',
  all: undefined,
};

const STATUS_VALUES = Object.keys(
  STATUS_GLYPH_STATES,
) as App.Platform.Enums.TicketListStatusFilter[];

export function useTicketListFilterProperties(
  availableFilters: App.Platform.Data.TicketListFilter[],
  stateCounts: App.Platform.Data.TicketListStateCounts,
  facetCounts: Record<string, Record<string, number>>,
): TicketListFilterProperty[] {
  const { t } = useTranslation();

  const { getFilterKindLabel, getFilterValueLabel, getStatusValueLabel } =
    useTicketListFilterLabels();

  const statusProperty: TicketListFilterProperty = {
    id: 'status',
    label: t('Status'),
    noFilterValue: 'all',

    options: STATUS_VALUES.map((value) => ({
      value,
      label: getStatusValueLabel(value),
      count: stateCounts[value],
      glyphState: STATUS_GLYPH_STATES[value],
    })),
  };

  return [
    statusProperty,
    ...availableFilters.map((filter) => {
      const countsByValue = facetCounts[filter.kind];

      return {
        id: filter.kind,
        label: getFilterKindLabel(filter.kind),
        noFilterValue: filter.values[0],
        options: filter.values.map((value) => ({
          value,
          label: getFilterValueLabel(filter.kind, value),
          count: countsByValue ? (countsByValue[value] ?? 0) : undefined,
        })),
      };
    }),
  ];
}
