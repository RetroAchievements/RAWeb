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

interface UserBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;

  game?: App.Platform.Data.Game;
  user?: App.Data.User;
}

export const UserBreadcrumbs: FC<UserBreadcrumbsProps> = ({ t_currentPageLabel, game, user }) => {
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          {user ? (
            <>
              <BaseBreadcrumbItem aria-label={user.displayName}>
                <BaseBreadcrumbLink href={route('user.show', { user: user.displayName })}>
                  {user.displayName}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>

              <BaseBreadcrumbSeparator />
            </>
          ) : null}

          {game ? (
            <>
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

              <BaseBreadcrumbSeparator />
            </>
          ) : null}

          <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
            <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
