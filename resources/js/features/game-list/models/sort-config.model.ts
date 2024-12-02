import type { SortDirection } from '@tanstack/react-table';
import type { IconType } from 'react-icons/lib';

import type { TranslatedString } from '@/types/i18next';

export type SortConfig = {
  [key in SortDirection]: { t_label: TranslatedString; icon?: IconType };
};
