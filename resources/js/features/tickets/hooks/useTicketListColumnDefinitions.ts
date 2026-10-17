import { useTranslation } from 'react-i18next';

import type { TicketListColumnDefinition } from '../models';
import { buildDateColumnDef } from '../utils/column-definitions/buildDateColumnDef';
import { buildHashColumnDef } from '../utils/column-definitions/buildHashColumnDef';
import { buildIdColumnDef } from '../utils/column-definitions/buildIdColumnDef';
import { buildTicketableColumnDef } from '../utils/column-definitions/buildTicketableColumnDef';
import { buildTicketMetadataColumnDef } from '../utils/column-definitions/buildTicketMetadataColumnDef';
import { buildUserColumnDef } from '../utils/column-definitions/buildUserColumnDef';
import { useTicketResolutionLabels } from './useTicketResolutionLabels';
import { useTicketTypeLabels } from './useTicketTypeLabels';

export function useTicketListColumnDefinitions(
  shouldShowGameTitle = true,
): TicketListColumnDefinition[] {
  const { t } = useTranslation();

  const resolutionLabels = useTicketResolutionLabels();
  const ticketTypeLabels = useTicketTypeLabels();

  const hardcoreLabel = t('Hardcore');
  const casualLabel = t('Casual');

  return [
    buildIdColumnDef({ t_label: t('ID') }),
    buildTicketableColumnDef({ t_label: t('Issue with'), shouldShowGameTitle }),
    buildTicketMetadataColumnDef({
      id: 'type',
      t_label: t('Issue type'),
      getText: (entry) => ticketTypeLabels[entry.type],
      widthClassName: 'w-[12em] flex-none',
    }),
    buildTicketMetadataColumnDef({
      id: 'mode',
      t_label: t('Mode'),
      getText: (entry) => {
        if (entry.hardcore === null) {
          return null;
        }

        return entry.hardcore ? hardcoreLabel : casualLabel;
      },
      widthClassName: 'w-[6em] flex-none',
    }),

    buildUserColumnDef({
      id: 'developer',
      t_label: t('Developer'),
      getUser: (entry) => entry.author,
    }),
    buildUserColumnDef({
      id: 'reporter',
      t_label: t('Reporter'),
      getUser: (entry) => entry.reporter,
    }),
    buildUserColumnDef({
      id: 'resolver',
      t_label: t('Resolved by'),
      getUser: (entry) => entry.resolver,
    }),
    buildTicketMetadataColumnDef({
      id: 'resolution',
      t_label: t('Resolution'),
      getText: (entry) => (entry.resolution ? resolutionLabels[entry.resolution] : null),
      widthClassName: 'w-[12em] flex-none',
    }),

    buildTicketMetadataColumnDef({
      id: 'emulator',
      t_label: t('Emulator'),
      getText: (entry) => entry.emulator?.name ?? null,
    }),
    buildTicketMetadataColumnDef({
      id: 'version',
      t_label: t('Version'),
      getText: (entry) => entry.emulatorVersion,
      widthClassName: 'w-[7em] flex-none',
    }),
    buildTicketMetadataColumnDef({
      id: 'core',
      t_label: t('Core'),
      getText: (entry) => entry.emulatorCore,
    }),
    buildHashColumnDef({ t_label: t('Hash') }),

    buildDateColumnDef({ id: 'age', t_label: t('Created') }),
    buildDateColumnDef({ id: 'resolvedAt', t_label: t('Resolved') }),
  ];
}
