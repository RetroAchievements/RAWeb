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
  system?: App.Platform.Data.System;
  t_currentPageLabel?: TranslatedString;
}

export const GameBreadcrumbs: FC<GameBreadcrumbsProps> = ({ t_currentPageLabel, game, system }) => {
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
                <BaseBreadcrumbLink asChild>
                  <InertiaLink href={route('system.game.index', system.id)}>
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
                  <BaseBreadcrumbLink href={route('game.show', { game: game.id })}>
                    <GameTitle title={game.title} />
                  </BaseBreadcrumbLink>
                ) : (
                  <BaseBreadcrumbPage>
                    <GameTitle title={game.title} />
                  </BaseBreadcrumbPage>
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
