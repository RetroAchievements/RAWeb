import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
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
  const { t } = useTranslation();

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem aria-label={t('All Games')}>
            <BaseBreadcrumbLink asChild>
              <InertiaLink href={route('game.index')} prefetch="desktop-hover-only">
                {t('All Games')}
              </InertiaLink>
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          {system ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={system.name}>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink
                    href={route('system.game.index', system.id)}
                    prefetch="desktop-hover-only"
                  >
                    {system.name}
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

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
                      href={route('game.show', { game: game.id })}
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
