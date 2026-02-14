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

/**
 * TODO this is intentionally quite duplicative with GameBreadcrumbs.
 * after the React code has matured a bit, settle on the correct
 * breadcrumbs abstraction to reduce this duplication.
 */

interface AchievementBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;

  achievement?: App.Platform.Data.Achievement;
  game?: App.Platform.Data.Game;
  gameAchievementSet?: App.Platform.Data.GameAchievementSet;
  system?: App.Platform.Data.System;
}

export const AchievementBreadcrumbs: FC<AchievementBreadcrumbsProps> = ({
  achievement,
  game,
  gameAchievementSet,
  system,
  t_currentPageLabel,
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
                    href={route('achievement.show', { achievementId: achievement.id })}
                    prefetch="desktop-hover-only"
                  >
                    {achievement.title}
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
