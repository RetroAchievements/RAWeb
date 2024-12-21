import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

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
  system?: App.Platform.Data.System;
}

export const AchievementBreadcrumbs: FC<AchievementBreadcrumbsProps> = ({
  achievement,
  game,
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
              <InertiaLink href={route('game.index')}>{t('All Games')}</InertiaLink>
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          {system ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={system.name}>
                <BaseBreadcrumbLink href={route('system.game.index', system.id)}>
                  {system.name}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {game ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={game.title}>
                <BaseBreadcrumbLink href={route('game.show', { game: game.id })}>
                  <GameTitle title={game.title} />
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {achievement ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={achievement.title}>
                <BaseBreadcrumbLink
                  href={route('achievement.show', { achievement: achievement.id })}
                >
                  {achievement.title}
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
