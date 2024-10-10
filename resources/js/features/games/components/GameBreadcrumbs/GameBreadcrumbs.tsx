import { Link } from '@inertiajs/react';
import type { FC } from 'react';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { GameTitle } from '@/common/components/GameTitle';

interface GameBreadcrumbsProps {
  currentPageLabel: string;

  game?: App.Platform.Data.Game;
  system?: App.Platform.Data.System;
}

export const GameBreadcrumbs: FC<GameBreadcrumbsProps> = ({ currentPageLabel, game, system }) => {
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem>
            <BaseBreadcrumbLink asChild>
              <Link href={route('game.index')}>All Games</Link>
            </BaseBreadcrumbLink>
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
                  <GameTitle title={game.title} />
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
