import type { SortDirection } from '@tanstack/react-table';
import type { IconType } from 'react-icons/lib';

export type SortConfig = {
  [key in SortDirection]: { t_label: string; icon?: IconType };
};
