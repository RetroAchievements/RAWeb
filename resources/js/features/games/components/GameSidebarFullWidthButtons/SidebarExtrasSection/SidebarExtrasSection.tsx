import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBookText, LuFileCode, LuSearch, LuTickets } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { usePageProps } from '@/common/hooks/usePageProps';

interface SidebarExtrasSectionProps {
  game: App.Platform.Data.Game;
}

export const SidebarExtrasSection: FC<SidebarExtrasSectionProps> = ({ game }) => {
  const { backingGame, can, numOpenTickets } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const showSubsetIndicator = backingGame.id !== game.id;

  return (
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
          <PlayableSidebarButton href={`/codenotes.php?g=${game.id}`} IconComponent={LuFileCode}>
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
  );
};
