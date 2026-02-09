import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTabs,
  BaseTabsContent,
  BaseTabsList,
  BaseTabsTrigger,
} from '@/common/components/+vendor/BaseTabs';
import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { useAchievementShowTabs } from '../../hooks/useAchievementShowTabs';
import type { AchievementShowTab } from '../../models';
import { AchievementCommentList } from '../AchievementCommentList';
import { AchievementGamePanel } from '../AchievementGamePanel';
import { AchievementHero } from '../AchievementHero';
import { AchievementInlineActions } from '../AchievementInlineActions';

export const AchievementShowRoot: FC = () => {
  const { achievement, backingGame, gameAchievementSet } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const { currentTab, setCurrentTab } = useAchievementShowTabs();

  // When the achievement belongs to a subset game, use the backing game for breadcrumbs.
  const breadcrumbGame = backingGame ?? achievement.game;

  return (
    <div>
      <AchievementBreadcrumbs
        t_currentPageLabel={achievement.title as TranslatedString}
        system={breadcrumbGame?.system}
        game={breadcrumbGame}
        gameAchievementSet={gameAchievementSet ?? undefined}
      />

      <div className="flex flex-col gap-3">
        <AchievementHero />

        <div className="md:hidden">
          <AchievementGamePanel />
        </div>

        <div className="flex flex-col gap-6">
          <AchievementInlineActions />

          <BaseTabs
            value={currentTab}
            onValueChange={(value) => setCurrentTab(value as AchievementShowTab)}
          >
            <div className="-mx-2.5 overflow-x-auto md:mx-0">
              <BaseTabsList
                className={cn(
                  'mb-3 flex w-max min-w-full justify-between rounded-none border-b border-neutral-600 py-0',
                  'md:w-auto md:min-w-0 md:justify-start md:gap-5 md:px-0',
                  'bg-neutral-900 light:bg-neutral-200/40 md:bg-transparent light:md:bg-transparent',
                  'light:pt-1',
                )}
              >
                {/*
                <BaseTabsTrigger value="tips" variant="underlined">
                  {t('Tips')}
                </BaseTabsTrigger>
                */}

                <BaseTabsTrigger value="comments" variant="underlined">
                  {t('Comments')}
                </BaseTabsTrigger>

                <BaseTabsTrigger value="unlocks" variant="underlined">
                  <span className="md:hidden">{t('Unlocks')}</span>
                  <span className="hidden md:block">{t('Recent Unlocks')}</span>
                </BaseTabsTrigger>

                <BaseTabsTrigger value="changelog" variant="underlined">
                  {t('Changelog')}
                </BaseTabsTrigger>
              </BaseTabsList>
            </div>

            <BaseTabsContent value="comments" className="md:-mt-1">
              <AchievementCommentList />
            </BaseTabsContent>

            <BaseTabsContent value="unlocks">{'AchievementRecentUnlocks'}</BaseTabsContent>

            <BaseTabsContent value="changelog">{'AchievementChangelog'}</BaseTabsContent>
          </BaseTabs>
        </div>
      </div>
    </div>
  );
};
