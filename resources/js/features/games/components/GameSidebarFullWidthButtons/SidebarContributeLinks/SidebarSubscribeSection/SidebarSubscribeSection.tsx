import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { SubsetIcon } from '@/common/components/SubsetIcon';
import { usePageProps } from '@/common/hooks/usePageProps';

interface SidebarSubscribeSectionProps {
  game: App.Platform.Data.Game;
}

export const SidebarSubscribeSection: FC<SidebarSubscribeSectionProps> = ({ game }) => {
  const { backingGame, isSubscribedToAchievementComments, isSubscribedToTickets } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const showSubsetIndicator = backingGame.id !== game.id;

  return (
    <PlayableSidebarButtonsSection headingLabel={t('Subscribe')}>
      <SubscribeToggleButton
        hasExistingSubscription={isSubscribedToAchievementComments}
        label={t('Achievement Comments')}
        subjectId={backingGame.id}
        subjectType="GameAchievements"
        className="h-9 justify-start px-4"
        extraIconSlot={showSubsetIndicator ? <SubsetIcon /> : undefined}
      />

      <SubscribeToggleButton
        hasExistingSubscription={isSubscribedToTickets}
        label={t('Tickets')}
        subjectId={backingGame.id}
        subjectType="GameTickets"
        className="h-9 justify-start px-4"
        extraIconSlot={showSubsetIndicator ? <SubsetIcon /> : undefined}
      />
    </PlayableSidebarButtonsSection>
  );
};
