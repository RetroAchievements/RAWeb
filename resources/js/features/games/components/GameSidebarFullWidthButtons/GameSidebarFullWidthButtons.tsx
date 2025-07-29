import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBookText, LuFileCode, LuFileText, LuSearch, LuTickets, LuWrench } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { GameCreateForumTopicButton } from '@/common/components/GameCreateForumTopicButton';
import { PlayableOfficialForumTopicButton } from '@/common/components/PlayableOfficialForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { usePageProps } from '@/common/hooks/usePageProps';

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
  const canShowManagement = can.manageGames;

  const userRoles = auth?.user.roles ?? [];
  const canShowDevelopment = userRoles.includes('developer'); // TODO || userRoles.includes('developer-junior')

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

          {game.guideUrl ? (
            <PlayableSidebarButton href={game.guideUrl} IconComponent={LuBookText} target="_blank">
              {t('Guide')}
            </PlayableSidebarButton>
          ) : null}
        </PlayableSidebarButtonsSection>
      ) : null}

      {canShowManagement ? (
        <PlayableSidebarButtonsSection headingLabel={t('Management')}>
          <PlayableSidebarButton href={`/manage/games/${game.id}`} IconComponent={LuWrench}>
            {t('Game Details')}
          </PlayableSidebarButton>

          {!game?.forumTopicId && can.createGameForumTopic ? (
            <GameCreateForumTopicButton game={game} />
          ) : null}
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
