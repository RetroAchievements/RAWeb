import type { FC } from 'react';
import { route } from 'ziggy-js';

import type { TranslatedString } from '@/types/i18next';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '../+vendor/BaseBreadcrumb';
import { GameTitle } from '../GameTitle';
import { InertiaLink } from '../InertiaLink';
import { RootBreadcrumbs } from '../RootBreadcrumbs';

interface AchievementBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;

  achievement?: App.Platform.Data.Achievement;
  game?: App.Platform.Data.Game;
  gameAchievementSet?: App.Platform.Data.GameAchievementSet;
  system?: App.Platform.Data.System;
  parentPage?: { href: string; t_label: TranslatedString };
  shouldCollapseRootCrumbs?: boolean;
}

export const AchievementBreadcrumbs: FC<AchievementBreadcrumbsProps> = ({
  achievement,
  game,
  gameAchievementSet,
  parentPage,
  system,
  t_currentPageLabel,
  shouldCollapseRootCrumbs = false,
}) => {
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <RootBreadcrumbs system={system} isCollapsed={shouldCollapseRootCrumbs} />

          {game ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={game.title}>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink
                    href={route('game.show', { game: game.id })}
                    prefetch="desktop-hover-only"
                  >
                    <GameTitle title={game.title} />
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {game && gameAchievementSet?.title ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={gameAchievementSet.title}>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink
                    href={route('game.show', {
                      game: game.id,
                      set: gameAchievementSet.achievementSet.id,
                    })}
                    prefetch="desktop-hover-only"
                  >
                    {gameAchievementSet.title}
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {achievement ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={achievement.title}>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink
                    href={route('achievement.show', { achievement: achievement.id })}
                    prefetch="desktop-hover-only"
                  >
                    {achievement.title}
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {parentPage ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={parentPage.t_label}>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink href={parentPage.href} prefetch="desktop-hover-only">
                    {parentPage.t_label}
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
            <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
