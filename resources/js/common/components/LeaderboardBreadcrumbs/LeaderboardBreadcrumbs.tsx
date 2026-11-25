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

interface LeaderboardBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;

  leaderboard?: App.Platform.Data.Leaderboard;
  game?: App.Platform.Data.Game;
  system?: App.Platform.Data.System;
  shouldCollapseRootCrumbs?: boolean;
}

export const LeaderboardBreadcrumbs: FC<LeaderboardBreadcrumbsProps> = ({
  leaderboard,
  game,
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

          {leaderboard ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={leaderboard.title}>
                <BaseBreadcrumbLink
                  href={route('leaderboard.show', { leaderboard: leaderboard.id })}
                >
                  {leaderboard.title}
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
