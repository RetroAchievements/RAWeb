import { type FC } from 'react';

import { GameAvatar } from '../GameAvatar';
import { InfiniteScrollLoadMore } from '../InfiniteScrollLoadMore';
import { RichPresenceMessage } from '../RichPresenceMessage';
import { UserAvatar } from '../UserAvatar';

interface ActivePlayerFeedListProps {
  onLoadMore: () => void;
  players: App.Community.Data.ActivePlayer[];
}

export const ActivePlayerFeedList: FC<ActivePlayerFeedListProps> = ({ onLoadMore, players }) => {
  return (
    <ol className="relative pt-1">
      {players.map((player) => (
        <li
          key={`active-player-${player.user.displayName}`}
          className="group flex gap-1 px-3 py-2 hover:bg-zinc-800 light:hover:bg-white"
        >
          <div className="flex gap-4">
            <UserAvatar {...player.user} showLabel={false} />
            <GameAvatar {...player.game} showLabel={false} />
          </div>

          <div className="flex flex-col">
            <div className="max-w-fit">
              <GameAvatar {...player.game} showImage={false} />
            </div>

            <p className="line-clamp-1 text-2xs" style={{ wordBreak: 'break-word' }}>
              <RichPresenceMessage
                message={player.user.richPresenceMsg}
                gameTitle={player.game.title}
              />
            </p>
          </div>
        </li>
      ))}

      <div className="absolute bottom-96">
        <InfiniteScrollLoadMore onLoadMore={onLoadMore} />
      </div>
    </ol>
  );
};
