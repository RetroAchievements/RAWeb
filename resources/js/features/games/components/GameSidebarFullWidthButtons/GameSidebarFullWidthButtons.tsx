import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBookText, LuFileCode, LuFileText, LuSearch, LuTickets, LuWrench } from 'react-icons/lu';

import { GameCreateForumTopicButton } from '@/common/components/GameCreateForumTopicButton';
import { PlayableOfficialForumTopicButton } from '@/common/components/PlayableOfficialForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';

interface GameSidebarFullWidthButtonsProps {
  game: App.Platform.Data.Game;
}

export const GameSidebarFullWidthButtons: FC<GameSidebarFullWidthButtonsProps> = ({ game }) => {
  const { auth, can, numCompatibleHashes, numOpenTickets } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  const canShowEssentialResources = numCompatibleHashes > 0 || game.forumTopicId;
  const canShowExtras = !!auth?.user;
  const canShowManagement = can.manageGames;

  if (!canShowEssentialResources && !canShowExtras && !canShowManagement) {
    return null;
  }

  return (
    <div className="flex flex-col gap-4">
      {canShowEssentialResources ? (
        <div className="flex flex-col gap-1">
          <p className="text-xs text-neutral-300 light:text-neutral-800">
            {t('Essential Resources')}
          </p>

          {numCompatibleHashes > 0 ? (
            <PlayableSidebarButton
              className="border-l-4 border-l-link"
              href={route('game.hashes.index', { game: game.id })}
              isInertiaLink={true}
              IconComponent={LuFileText}
              count={numCompatibleHashes}
            >
              {t('Supported Game Files')}
            </PlayableSidebarButton>
          ) : null}

          <PlayableOfficialForumTopicButton game={game} />
        </div>
      ) : null}

      {canShowExtras ? (
        <div className="flex flex-col gap-1">
          <p className="text-xs text-neutral-300 light:text-neutral-800">{t('Extras')}</p>

          <PlayableSidebarButton
            href={route('game.suggestions.similar', { game: game.id })}
            isInertiaLink={true}
            IconComponent={LuSearch}
          >
            {t('Find Similar Games')}
          </PlayableSidebarButton>

          {game.system?.active ? (
            <div className="grid grid-cols-2 gap-1">
              <PlayableSidebarButton
                href={`/codenotes.php?g=${game.id}`}
                IconComponent={LuFileCode}
              >
                {t('Memory')}
              </PlayableSidebarButton>

              <PlayableSidebarButton
                href={route('game.tickets', { game: game.id, 'filter[achievement]': 'core' })}
                IconComponent={LuTickets}
                count={numOpenTickets}
              >
                {t('Tickets')}
              </PlayableSidebarButton>
            </div>
          ) : null}

          {game.guideUrl ? (
            <PlayableSidebarButton href="#" IconComponent={LuBookText} target="_blank">
              {t('Guide')}
            </PlayableSidebarButton>
          ) : null}
        </div>
      ) : null}

      {canShowManagement ? (
        <div className="flex flex-col gap-1">
          <p className="text-xs text-neutral-300 light:text-neutral-800">{t('Manage')}</p>

          <PlayableSidebarButton href={`/manage/games/${game.id}`} IconComponent={LuWrench}>
            {t('Game Details')}
          </PlayableSidebarButton>

          {!game?.forumTopicId && can.createGameForumTopic ? (
            <GameCreateForumTopicButton game={game} />
          ) : null}
        </div>
      ) : null}
    </div>
  );
};
