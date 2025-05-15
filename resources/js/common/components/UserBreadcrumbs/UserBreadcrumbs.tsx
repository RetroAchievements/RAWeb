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

interface UserBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;

  game?: App.Platform.Data.Game;
  user?: App.Data.User;
}

export const UserBreadcrumbs: FC<UserBreadcrumbsProps> = ({ t_currentPageLabel, game, user }) => {
  const { t } = useTranslation();

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem aria-label={t('All Users')}>
            <BaseBreadcrumbLink href="/userList.php">{t('All Users')}</BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          {user ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem aria-label={user.displayName}>
                <BaseBreadcrumbLink href={route('user.show', { user: user.displayName })}>
                  {user.displayName}
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

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
            <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
