import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBookText, LuFileCode, LuFileText, LuSearch, LuTickets, LuWrench } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { GameCreateForumTopicButton } from '@/common/components/GameCreateForumTopicButton';
import { PlayableOfficialForumTopicButton } from '@/common/components/PlayableOfficialForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { SidebarDevelopmentSection } from './SidebarDevelopmentSection';

interface GameSidebarFullWidthButtonsProps {
  game: App.Platform.Data.Game;
}

export const GameSidebarFullWidthButtons: FC<GameSidebarFullWidthButtonsProps> = ({ game }) => {
  const { auth, backingGame, can, numCompatibleHashes, numOpenTickets } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  const canShowEssentialResources = numCompatibleHashes > 0 || game.forumTopicId;
  const canShowExtras = !!auth?.user;
  const canShowManagement =
    can.manageGames || can.updateGame || can.manageGameHashes || can.updateAnyAchievementSetClaim;

  const userRoles = auth?.user.roles ?? [];
  const canShowDevelopment =
    userRoles.includes('developer') || userRoles.includes('developer-junior');

  const showSubsetIndicator = backingGame.id !== game.id;

  if (!canShowEssentialResources && !canShowExtras && !canShowManagement) {
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

      {canShowExtras ? (
        <PlayableSidebarButtonsSection headingLabel={t('Extras')}>
          <PlayableSidebarButton
            href={route('game.suggestions.similar', { game: game.id })}
            isInertiaLink={true}
            IconComponent={LuSearch}
          >
            {t('Find Related Games')}
          </PlayableSidebarButton>

          {can.manageGames || game.system?.active ? (
            <div className="grid grid-cols-2 gap-1">
              <PlayableSidebarButton
                href={`/codenotes.php?g=${game.id}`}
                IconComponent={LuFileCode}
              >
                {t('Memory')}
              </PlayableSidebarButton>

              <PlayableSidebarButton
                href={route('game.tickets', {
                  game: backingGame.id,
                  'filter[achievement]': 'core',
                })}
                IconComponent={LuTickets}
                count={numOpenTickets}
                showSubsetIndicator={showSubsetIndicator}
              >
                {t('Tickets')}
              </PlayableSidebarButton>
            </div>
          ) : null}

          {backingGame.guideUrl ? (
            <PlayableSidebarButton
              href={backingGame.guideUrl}
              IconComponent={LuBookText}
              target="_blank"
            >
              {t('Guide')}
            </PlayableSidebarButton>
          ) : null}
        </PlayableSidebarButtonsSection>
      ) : null}

      {canShowManagement ? (
        <PlayableSidebarButtonsSection headingLabel={t('Management')}>
          {!backingGame?.forumTopicId && can.createGameForumTopic ? (
            <GameCreateForumTopicButton game={backingGame} />
          ) : null}

          {can.manageGames ? (
            <PlayableSidebarButton
              href={can.updateGame ? `/manage/games/${game.id}/edit` : `/manage/games/${game.id}`}
              IconComponent={LuWrench}
              target="_blank"
            >
              {can.updateGame ? t('Edit Game Details') : t('View Game Details')}
            </PlayableSidebarButton>
          ) : null}

          <div
            className={cn(
              'gap-1',
              can.manageGameHashes && can.updateAnyAchievementSetClaim
                ? 'grid grid-cols-2'
                : 'flex flex-col',
            )}
          >
            {can.manageGameHashes ? (
              <PlayableSidebarButton
                href={`/manage/games/${backingGame.id}/hashes`}
                IconComponent={LuWrench}
              >
                {can.manageGameHashes && can.updateAnyAchievementSetClaim
                  ? t('Hashes')
                  : t('Manage Hashes')}
              </PlayableSidebarButton>
            ) : null}

            {can.updateAnyAchievementSetClaim ? (
              <PlayableSidebarButton
                href={`/manageclaims.php?g=${backingGame.id}`}
                IconComponent={LuWrench}
              >
                {can.manageGameHashes && can.updateAnyAchievementSetClaim
                  ? t('Claims')
                  : t('Manage Claims')}
              </PlayableSidebarButton>
            ) : null}
          </div>
        </PlayableSidebarButtonsSection>
      ) : null}

      {canShowDevelopment ? (
        <PlayableSidebarButtonsSection headingLabel={t('Development')}>
          <SidebarDevelopmentSection />
        </PlayableSidebarButtonsSection>
      ) : null}
    </div>
  );
};
