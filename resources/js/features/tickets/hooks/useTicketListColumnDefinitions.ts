import { useTranslation } from 'react-i18next';

import type { TicketListColumnDefinition } from '../models';
import { buildAgeColumnDef } from '../utils/column-definitions/buildAgeColumnDef';
import { buildGameColumnDef } from '../utils/column-definitions/buildGameColumnDef';
import { buildHashColumnDef } from '../utils/column-definitions/buildHashColumnDef';
import { buildIdColumnDef } from '../utils/column-definitions/buildIdColumnDef';
import { buildTicketableColumnDef } from '../utils/column-definitions/buildTicketableColumnDef';
import { buildTicketMetadataColumnDef } from '../utils/column-definitions/buildTicketMetadataColumnDef';
import { buildUserColumnDef } from '../utils/column-definitions/buildUserColumnDef';

export function useTicketListColumnDefinitions(): TicketListColumnDefinition[] {
  const { t } = useTranslation();

  const ticketTypeLabels: Record<App.Community.Enums.TicketType, string> = {
    did_not_cancel: t('Did not cancel'),
    did_not_start: t('Did not start'),
    did_not_submit: t('Did not submit'),
    did_not_trigger: t('Did not trigger'),
    submitted_wrong_value: t('Submitted wrong value'),
    triggered_at_wrong_time: t('Triggered at the wrong time'),
  };

  const hardcoreLabel = t('Hardcore');
  const casualLabel = t('Casual');

  return [
    buildIdColumnDef({ t_label: t('ID') }),
    buildTicketableColumnDef({ t_label: t('Issue with') }),
    buildGameColumnDef({ t_label: t('Game') }),
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

    buildAgeColumnDef({ t_label: t('Age') }),
  ];
}
