import type { IconType } from 'react-icons/lib';

import type { TranslatedString } from '@/types/i18next';

export interface Shortcode {
  icon: IconType;
  t_label: TranslatedString;
  start: string;
  end: string;
}
