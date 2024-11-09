import { useTranslation } from 'react-i18next';
import { RxArrowDown, RxArrowUp } from 'react-icons/rx';

import type { SortConfig, SortConfigKind } from '../models';

export function useSortConfigs() {
  const { t } = useTranslation();

  /**
   * The order of `asc` and `desc` determines the order they'll
   * appear in the menu as menuitems.
   */
  const sortConfigs: Record<SortConfigKind, SortConfig> = {
    default: {
      asc: { t_label: t('Ascending (A - Z)') },
      desc: { t_label: t('Descending (Z - A)') },
    },
    date: {
      asc: { t_label: t('Earliest') },
      desc: { t_label: t('Latest') },
    },
    quantity: {
      desc: { t_label: t('More'), icon: RxArrowUp },
      asc: { t_label: t('Less'), icon: RxArrowDown },
    },
    boolean: {
      desc: { t_label: t('Yes first'), icon: RxArrowUp },
      asc: { t_label: t('No first'), icon: RxArrowDown },
    },
  };

  return { sortConfigs };
}
