import { useSetAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { isResetProgressDialogOpenAtom } from '../../state/achievements.atoms';

export const AchievementInlineActions: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const setIsResetProgressDialogOpen = useSetAtom(isResetProgressDialogOpenAtom);

  return (
    <div className="flex flex-col gap-2 text-xs md:flex-row md:items-center md:justify-between">
      <div className="flex divide-x divide-neutral-700">
        <InertiaLink
          href={route('achievement.report-issue', { achievement })}
          prefetch="desktop-hover-only"
        >
          <span className="pr-3">{t('Report an issue')}</span>
        </InertiaLink>

        {achievement.numUnresolvedTickets ? (
          <a href={route('achievement.tickets', { achievement: achievement.id })} className="px-3">
            {t('openTicketCount', {
              count: achievement.numUnresolvedTickets,
              val: achievement.numUnresolvedTickets,
            })}
          </a>
        ) : (
          <p className="px-3 italic text-neutral-600">{t('No open tickets')}</p>
        )}
      </div>

      {achievement.unlockedAt || achievement.unlockedHardcoreAt ? (
        <div className="flex divide-x divide-neutral-700">
          <BaseButton
            onClick={() => setIsResetProgressDialogOpen(true)}
            variant="destructive"
            size="xs"
          >
            {t('Reset progress')}
          </BaseButton>
        </div>
      ) : null}
    </div>
  );
};
