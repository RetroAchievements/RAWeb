import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuFileCheck, LuFlagTriangleRight, LuWrench } from 'react-icons/lu';

import { GameCreateForumTopicButton } from '@/common/components/GameCreateForumTopicButton';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { PlayableSidebarButtonsSection } from '@/common/components/PlayableSidebarButtonsSection';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

interface SidebarManagementSectionProps {
  game: App.Platform.Data.Game;
}

export const SidebarManagementSection: FC<SidebarManagementSectionProps> = ({ game }) => {
  const { backingGame, can } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const isViewingSubset = game.id !== backingGame.id;

  return (
    <PlayableSidebarButtonsSection headingLabel={t('Management')}>
      {!backingGame?.forumTopicId && can.createGameForumTopic ? (
        <GameCreateForumTopicButton game={backingGame} />
      ) : null}

      {can.manageGames && !isViewingSubset ? (
        <PlayableSidebarButton
          href={can.updateGame ? `/manage/games/${game.id}/edit` : `/manage/games/${game.id}`}
          IconComponent={LuWrench}
          target="_blank"
        >
          {can.updateGame ? t('Edit Game Details') : t('View Game Details')}
        </PlayableSidebarButton>
      ) : null}

      {can.manageGames && isViewingSubset ? (
        <>
          <PlayableSidebarButton
            href={can.updateGame ? `/manage/games/${game.id}/edit` : `/manage/games/${game.id}`}
            IconComponent={LuWrench}
            target="_blank"
          >
            {can.updateGame ? t('Edit Base Game Details') : t('View Base Game Details')}
          </PlayableSidebarButton>

          <PlayableSidebarButton
            href={
              can.updateGame
                ? `/manage/games/${backingGame.id}/edit`
                : `/manage/games/${backingGame.id}`
            }
            IconComponent={LuWrench}
            showSubsetIndicator={game.id !== backingGame.id}
            target="_blank"
          >
            {can.updateGame ? t('Edit Subset Game Details') : t('View Subset Game Details')}
          </PlayableSidebarButton>
        </>
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
            target="_blank"
            IconComponent={LuFileCheck}
            showSubsetIndicator={game.id !== backingGame.id}
          >
            {can.manageGameHashes && can.updateAnyAchievementSetClaim
              ? t('Hashes')
              : t('Manage Hashes')}
          </PlayableSidebarButton>
        ) : null}

        {can.updateAnyAchievementSetClaim ? (
          <PlayableSidebarButton
            href={`/manageclaims.php?g=${backingGame.id}`}
            target="_blank"
            IconComponent={LuFlagTriangleRight}
            showSubsetIndicator={game.id !== backingGame.id}
          >
            {can.manageGameHashes && can.updateAnyAchievementSetClaim
              ? t('Claims')
              : t('Manage Claims')}
          </PlayableSidebarButton>
        ) : null}
      </div>
    </PlayableSidebarButtonsSection>
  );
};
