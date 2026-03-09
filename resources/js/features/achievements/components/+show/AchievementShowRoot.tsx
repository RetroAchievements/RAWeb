import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTabsContent } from '@/common/components/+vendor/BaseTabs';
import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { VideoEmbed } from '@/common/components/VideoEmbed';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import type { TabConfig } from '../../models';
import { AchievementChangelog } from '../AchievementChangelog';
import { AchievementCommentList } from '../AchievementCommentList';
import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementHero } from '../AchievementHero';
import { AchievementInlineActions } from '../AchievementInlineActions';
import { AchievementRecentUnlocks } from '../AchievementRecentUnlocks';
import { AchievementTabs } from '../AchievementTabsList';
import { ResetProgressDialog } from '../ResetProgressDialog';

export const AchievementShowRoot: FC = () => {
  const { achievement, backingGame, gameAchievementSet } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const tabConfigs: TabConfig[] = [
    { value: 'comments', label: t('Comments') },
    ...(achievement.embedUrl ? [{ value: 'tips' as const, label: t('Media') }] : []),
    { value: 'unlocks', label: t('Recent Unlocks'), mobileLabel: t('Unlocks') },
    { value: 'changelog', label: t('Changelog') },
  ];

  // When the achievement belongs to a subset game, use the backing game for breadcrumbs.
  const breadcrumbGame = backingGame ?? achievement.game;

  return (
    <div>
      {achievement.unlockedAt || achievement.unlockedHardcoreAt ? <ResetProgressDialog /> : null}

      <AchievementBreadcrumbs
        t_currentPageLabel={achievement.title as TranslatedString}
        system={breadcrumbGame?.system}
        game={breadcrumbGame}
        gameAchievementSet={gameAchievementSet ?? undefined}
      />

      <div className="flex flex-col gap-3">
        <AchievementHero />

        <div className="lg:hidden">
          <AchievementGamePanel />
        </div>

        <div className="flex flex-col gap-6">
          <AchievementInlineActions />

          <AchievementTabs tabConfigs={tabConfigs}>
            <BaseTabsContent value="comments" className="md:-mt-1">
              <AchievementCommentList />
            </BaseTabsContent>

            {achievement.embedUrl ? (
              <BaseTabsContent value="tips">
                {/\.(png|jpg|jpeg|gif|webp)$/i.test(achievement.embedUrl) ? (
                  <a href={achievement.embedUrl} target="_blank" rel="noreferrer">
                    <img src={achievement.embedUrl} alt={t('Media')} className="max-w-full" />
                  </a>
                ) : (
                  <VideoEmbed src={achievement.embedUrl} />
                )}
              </BaseTabsContent>
            ) : null}

            <BaseTabsContent value="unlocks">
              <AchievementRecentUnlocks />
            </BaseTabsContent>

            <BaseTabsContent value="changelog">
              <AchievementChangelog />
            </BaseTabsContent>
          </AchievementTabs>
        </div>
      </div>
    </div>
  );
};
