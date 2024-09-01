import { usePage } from '@inertiajs/react';
import type { FC } from 'react';
import { LuSave } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { Embed } from '@/common/components/Embed/Embed';

import { GameBreadcrumbs } from '../GameBreadcrumbs';
import { GameHeading } from '../GameHeading/GameHeading';
import { HashesList } from './HashesList';

export const HashesMainRoot: FC = () => {
  const {
    props: { can, game, hashes },
  } = usePage<App.Platform.Data.GameHashesPageProps>();

  return (
    <div>
      <GameBreadcrumbs game={game} system={game.system} currentPageLabel="Supported Game Files" />
      <GameHeading game={game}>Supported Game Files</GameHeading>

      <div className="flex flex-col gap-5">
        {can.manageGameHashes ? (
          <a
            href={route('game.hash.manage', { game: game.id })}
            className={baseButtonVariants({
              size: 'sm',
              className: 'flex items-center gap-1 sm:max-w-fit',
            })}
          >
            <LuSave className="h-5 w-5" />
            <span className="">Manage Hashes</span>
          </a>
        ) : null}

        <Embed className="flex flex-col gap-4">
          <p className="font-bold">
            This page shows you what ROM hashes are compatible with this game's achievements.
          </p>

          <p>
            {game.forumTopicId ? (
              <>
                Additional information for these hashes may be listed on{' '}
                <a href={`/viewtopic.php?t=${game.forumTopicId}`}>
                  the game's official forum topic
                </a>
                .
              </>
            ) : null}{' '}
            Details on how the hash is generated for each system can be found{' '}
            <a
              href="https://docs.retroachievements.org/developer-docs/game-identification.html"
              target="_blank"
            >
              here
            </a>
            .{' '}
          </p>
        </Embed>

        <div className="flex flex-col gap-1">
          <p>
            There {hashes.length === 1 ? 'is' : 'are'} currently{' '}
            <span className="font-bold">{hashes.length}</span> supported game file{' '}
            {hashes.length === 1 ? 'hash' : 'hashes'} registered for this game.
          </p>

          <HashesList />
        </div>
      </div>
    </div>
  );
};
