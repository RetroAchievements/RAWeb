import { useTranslation } from 'react-i18next';

import type { TicketListFilterProperty, TicketListFilterPropertyOption } from '../models';
import { TICKET_SIGNAL_VERDICT_APPEARANCE } from '../utils/ticketSignalVerdictAppearance';
import { TICKET_STATE_GLYPHS } from '../utils/ticketStateGlyphs';
import { useTicketListFilterLabels } from './useTicketListFilterLabels';

type OptionGlyph = TicketListFilterPropertyOption['glyph'];

const STATUS_GLYPHS: Record<App.Platform.Enums.TicketListStatusFilter, OptionGlyph> = {
  unresolved: undefined,
  open: TICKET_STATE_GLYPHS.open,
  request: TICKET_STATE_GLYPHS.request,
  resolved: TICKET_STATE_GLYPHS.resolved,
  closed: TICKET_STATE_GLYPHS.closed,
  quarantined: TICKET_STATE_GLYPHS.quarantined,
  all: undefined,
};

const STATUS_VALUES = Object.keys(STATUS_GLYPHS) as App.Platform.Enums.TicketListStatusFilter[];

const GLYPHS_BY_FILTER_VALUE: Partial<
  Record<App.Platform.Enums.TicketListFilterKind, Record<string, OptionGlyph>>
> = {
  signal: TICKET_SIGNAL_VERDICT_APPEARANCE,
};

export function useTicketListFilterProperties(
  availableFilters: App.Platform.Data.TicketListFilter[],
  stateCounts: App.Platform.Data.TicketListStateCounts,
  facetCounts: Record<string, Record<string, number>>,
  hasStatusFilter: boolean,
): TicketListFilterProperty[] {
  const { t } = useTranslation();

  const { getFilterKindLabel, getFilterKindPlaceholder, getFilterValueLabel, getStatusValueLabel } =
    useTicketListFilterLabels();

  const statusProperty: TicketListFilterProperty = {
    id: 'status',
    label: t('Status'),
    noFilterValue: 'all',

    options: STATUS_VALUES.map((value) => ({
      value,
      count: stateCounts[value],
      glyph: STATUS_GLYPHS[value],
      label: getStatusValueLabel(value),
    })),
  };

  return [
    ...(hasStatusFilter ? [statusProperty] : []),
    ...availableFilters.map((filter) => {
      if (filter.isFreeText) {
        return {
          id: filter.kind,
          label: getFilterKindLabel(filter.kind),
          noFilterValue: '',
          options: [],
          isFreeText: true,
          placeholder: getFilterKindPlaceholder(filter.kind),
        };
      }

      const countsByValue = facetCounts[filter.kind];

      return {
        id: filter.kind,
        label: getFilterKindLabel(filter.kind),
        noFilterValue: filter.values[0],
        options: filter.values.map((value) => ({
          value,
          count: countsByValue ? (countsByValue[value] ?? 0) : undefined,
          glyph: GLYPHS_BY_FILTER_VALUE[filter.kind]?.[value],
          label: getFilterValueLabel(filter.kind, value, filter.valueLabels),
        })),
      };
    }),
  ];
}
