import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import { type FC, useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronDown } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseCollapsible,
  BaseCollapsibleContent,
  BaseCollapsibleTrigger,
} from '@/common/components/+vendor/BaseCollapsible';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';

interface PopulatedPlayerCompletionsProps {
  followedPlayerCompletions: App.Platform.Data.FollowedPlayerCompletion[];
  game: App.Platform.Data.Game;
}

export const PopulatedPlayerCompletions: FC<PopulatedPlayerCompletionsProps> = ({
  followedPlayerCompletions,
  game,
}) => {
  const { t } = useTranslation();

  const [isRemainingContentOpen, setIsRemainingContentOpen] = useState(false);

  const remainingContentRef = useRef<HTMLDivElement>(null);
  const [remainingContentHeight, setRemainingContentHeight] = useState(0);

  useEffect(() => {
    if (remainingContentRef.current) {
      setRemainingContentHeight(remainingContentRef.current.offsetHeight);
    }
  }, [isRemainingContentOpen]);

  // If there are 11 total players (10 + 1), show all of them at once.
  const shouldShowAllAtOnce = followedPlayerCompletions.length === 11;

  // If showing all at once, display all completions, otherwise only display the first 10.
  const initialVisible = shouldShowAllAtOnce
    ? followedPlayerCompletions
    : followedPlayerCompletions.slice(0, 10);

  // Only calculate remaining if we're not showing all at once.
  const remaining = shouldShowAllAtOnce ? [] : followedPlayerCompletions.slice(10);

  return (
    <div>
      <PlayerCompletionList completions={initialVisible} game={game} />

      {remaining.length > 0 ? (
        <BaseCollapsible open={isRemainingContentOpen} onOpenChange={setIsRemainingContentOpen}>
          <BaseCollapsibleTrigger asChild>
            <BaseButton size="sm" className="mt-2 w-full justify-center border-none">
              {t('See {{val, number}} more', { val: remaining.length })}

              <LuChevronDown
                className={cn(
                  'ml-1 size-4 transition-transform duration-300',
                  isRemainingContentOpen ? 'rotate-180' : 'rotate-0',
                )}
              />
            </BaseButton>
          </BaseCollapsibleTrigger>

          <AnimatePresence initial={false}>
            {isRemainingContentOpen ? (
              <BaseCollapsibleContent forceMount asChild>
                <motion.div
                  initial={{ height: 0 }}
                  animate={{ height: remainingContentHeight }}
                  exit={{ height: 0 }}
                  transition={{
                    duration: 0.3,
                    ease: [0.4, 0, 0.2, 1],
                  }}
                  className="overflow-hidden"
                >
                  <div ref={remainingContentRef}>
                    <PlayerCompletionList completions={remaining} game={game} />
                  </div>
                </motion.div>
              </BaseCollapsibleContent>
            ) : null}
          </AnimatePresence>
        </BaseCollapsible>
      ) : null}
    </div>
  );
};

interface PlayerCompletionListProps {
  completions: App.Platform.Data.FollowedPlayerCompletion[];
  game: App.Platform.Data.Game;
}

const PlayerCompletionList: FC<PlayerCompletionListProps> = ({ completions, game }) => {
  return (
    <ul className="zebra-list">
      {completions.map((completion) => (
        <li
          className="flex w-full items-center justify-between gap-2 p-2"
          key={`completion-${completion.user.displayName}`}
        >
          <span className="lg:w-[130px] xl:w-[176px]">
            <UserAvatar {...completion.user} size={24} labelClassName="truncate" />
          </span>
          <PlayerGameProgressBar
            playerGame={completion.playerGame}
            game={game}
            variant="event"
            width={108}
            href={route('game.compare-unlocks', {
              game: game.id,
              user: completion.user.displayName,
            })}
          />
        </li>
      ))}
    </ul>
  );
};
