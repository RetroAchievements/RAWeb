import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';
import { LuCheck, LuCircleCheck } from 'react-icons/lu';

import type { TranslatedString } from '@/types/i18next';

interface Meta {
  label: TranslatedString;
  icon: IconType;
}

export function useEventStateMeta() {
  const { t } = useTranslation();

  const eventStateMeta: Record<App.Platform.Enums.EventState, Meta> = {
    active: {
      label: t('Active'),
      icon: LuCheck,
    },
    evergreen: {
      label: t('No Time Limit'),
      icon: LuCheck,
    },
    concluded: {
      label: t('Concluded'),
      icon: LuCircleCheck,
    },
  };

  return { eventStateMeta };
}
