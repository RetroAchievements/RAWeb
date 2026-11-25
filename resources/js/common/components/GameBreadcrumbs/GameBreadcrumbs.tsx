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

interface GameBreadcrumbsProps {
  game?: App.Platform.Data.Game;
  gameAchievementSet?: App.Platform.Data.GameAchievementSet;
  system?: App.Platform.Data.System;
  t_currentPageLabel?: TranslatedString;
}

export const GameBreadcrumbs: FC<GameBreadcrumbsProps> = ({
  t_currentPageLabel,
  game,
  gameAchievementSet,
  system,
}) => {
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <RootBreadcrumbs system={system} />

          {game ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={game.title}>
                {t_currentPageLabel ? (
                  <BaseBreadcrumbLink asChild>
                    <InertiaLink
                      href={route('game.show', { game: game.id })}
                      prefetch="desktop-hover-only"
                    >
                      <GameTitle title={game.title} />
                    </InertiaLink>
                  </BaseBreadcrumbLink>
                ) : null}

                {!t_currentPageLabel && !gameAchievementSet?.title ? (
                  <BaseBreadcrumbPage>
                    <GameTitle title={game.title} />
                  </BaseBreadcrumbPage>
                ) : null}

                {!t_currentPageLabel && gameAchievementSet?.title ? (
                  <BaseBreadcrumbLink asChild>
                    <InertiaLink
                      href={route('game.show', { game: game.id })}
                      prefetch="desktop-hover-only"
                    >
                      <GameTitle title={game.title} />
                    </InertiaLink>
                  </BaseBreadcrumbLink>
                ) : null}
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {game && gameAchievementSet?.title ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={gameAchievementSet.title}>
                {t_currentPageLabel ? (
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
                ) : (
                  <BaseBreadcrumbPage>{gameAchievementSet.title}</BaseBreadcrumbPage>
                )}
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {t_currentPageLabel ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
                <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
              </BaseBreadcrumbItem>
            </>
          ) : null}
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
