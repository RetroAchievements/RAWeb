import type { ColumnDef } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';

import { buildAgeColumnDef } from '../utils/column-definitions/buildAgeColumnDef';
import { buildGameColumnDef } from '../utils/column-definitions/buildGameColumnDef';
import { buildIdColumnDef } from '../utils/column-definitions/buildIdColumnDef';
import { buildTicketableColumnDef } from '../utils/column-definitions/buildTicketableColumnDef';
import { buildUserColumnDef } from '../utils/column-definitions/buildUserColumnDef';

export function useTicketListColumnDefinitions(): ColumnDef<App.Platform.Data.TicketListEntry>[] {
  const { t } = useTranslation();

  return [
    buildIdColumnDef({ t_label: t('ID') }),
    buildTicketableColumnDef({ t_label: t('Issue with') }),
    buildGameColumnDef({ t_label: t('Game') }),

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

    buildAgeColumnDef({ t_label: t('Age') }),
  ];
}
