import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuFileText } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { PlayableOfficialForumTopicButton } from '@/common/components/PlayableOfficialForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SidebarContributeLinks } from './SidebarContributeLinks';
import { SidebarExtrasSection } from './SidebarExtrasSection';

interface GameSidebarFullWidthButtonsProps {
  game: App.Platform.Data.Game;
}

export const GameSidebarFullWidthButtons: FC<GameSidebarFullWidthButtonsProps> = ({ game }) => {
  const { auth, backingGame, can, numCompatibleHashes } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const canShowEssentialResources = numCompatibleHashes > 0 || game.forumTopicId;
  const canShowExtras = !!auth?.user;
  const canShowManagement = !!(
    can.manageGames ||
    can.updateGame ||
    can.manageGameHashes ||
    can.updateAnyAchievementSetClaim
  );

  const userRoles = auth?.user.roles ?? [];
  const canShowDevelopmentAndSubscribe =
    userRoles.includes('developer') || userRoles.includes('developer-junior');

  if (
    !canShowEssentialResources &&
    !canShowExtras &&
    !canShowManagement &&
    !canShowDevelopmentAndSubscribe
  ) {
    return null;
  }

  return (
    <div className="flex flex-col gap-4">
      {canShowEssentialResources ? (
        <PlayableSidebarButtonsSection headingLabel={t('Essential Resources')}>
          {numCompatibleHashes > 0 ? (
            <PlayableSidebarButton
              className="border-l-4 border-l-link"
              href={route('game.hashes.index', { game: backingGame.id })}
              isInertiaLink={true}
              IconComponent={LuFileText}
              count={numCompatibleHashes}
            >
              {t('Supported Game Files')}
            </PlayableSidebarButton>
          ) : null}

          <PlayableOfficialForumTopicButton backingGame={backingGame} game={game} />
        </PlayableSidebarButtonsSection>
      ) : null}

      {canShowExtras ? <SidebarExtrasSection game={game} /> : null}

      {canShowDevelopmentAndSubscribe || canShowManagement ? (
        <SidebarContributeLinks
          canShowDevelopmentAndSubscribe={canShowDevelopmentAndSubscribe}
          canShowManagement={canShowManagement}
        />
      ) : null}
    </div>
  );
};
