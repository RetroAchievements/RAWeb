import type { FC } from 'react';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';

/**
 * TODO this is intentionally quite duplicative with GameBreadcrumbs.
 * after the React code has matured a bit, settle on the correct
 * breadcrumbs abstraction to reduce this duplication.
 */

interface AchievementBreadcrumbsProps {
  currentPageLabel: string;

  achievement?: App.Platform.Data.Achievement;
  game?: App.Platform.Data.Game;
  system?: App.Platform.Data.System;
}

export const AchievementBreadcrumbs: FC<AchievementBreadcrumbsProps> = ({
  currentPageLabel,
  achievement,
  game,
  system,
}) => {
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem>
            <BaseBreadcrumbLink href="/gameList.php">All Games</BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          {system ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem>
                <BaseBreadcrumbLink href={route('system.game.index', system.id)}>
                  {system.name}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {game ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem>
                <BaseBreadcrumbLink href={route('game.show', { game: game.id })}>
                  {game.title}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {achievement ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem>
                <BaseBreadcrumbLink
                  href={route('achievement.show', { achievement: achievement.id })}
                >
                  {achievement.title}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem>
            <BaseBreadcrumbPage>{currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
