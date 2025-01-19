import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

export const AotwUnlockedIndicator: FC = () => {
  const { achievementOfTheWeek } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!achievementOfTheWeek?.doesUserHaveUnlock) {
    return null;
  }

  return (
    <div data-testid="aotw-progress" className="bg-neutral-950 px-2 py-1.5">
      <p className="text-center text-xs text-neutral-300">{t('Unlocked')}</p>
    </div>
  );
};
