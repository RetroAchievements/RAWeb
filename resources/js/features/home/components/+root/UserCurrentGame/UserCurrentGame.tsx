import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronRight } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

export const UserCurrentGame: FC = () => {
  const { userCurrentGame, userCurrentGameMinutesAgo } =
    usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!userCurrentGame || userCurrentGameMinutesAgo === null) {
    return null;
  }

  const treatmentKind: 'active' | 'recent' = userCurrentGameMinutesAgo < 5 ? 'active' : 'recent';

  return (
    <a
      href={route('game.show', { game: userCurrentGame.id })}
      className={cn(
        'group lg:hover:bg-neutral-950/30 lg:hover:light:bg-neutral-100',
        '-mx-4 -mt-4 w-[calc(100%+2rem)] bg-embed px-4 py-2',
        'sm:-mt-10 sm:mb-0',
        'md:-mx-6 md:mt-[-2.3rem] md:w-[calc(100%+3rem)]',
        '-mb-4 lg:-mx-2 lg:-mb-2 lg:mt-0 lg:w-[calc(100%+1rem)] lg:rounded-lg',
        'flex items-center justify-between gap-2',
      )}
    >
      <div className="flex items-center gap-2">
        <div className="relative">
          <img src={userCurrentGame.badgeUrl} width={20} height={20} className="rounded-sm" />

          {treatmentKind === 'active' ? (
            <div className="absolute -right-0.5 -top-0.5 size-2 rounded-full bg-green-500" />
          ) : null}
        </div>

        <div className="flex gap-2">
          <span className="text-neutral-400">
            {t(treatmentKind === 'active' ? 'In game:' : 'Recently played:', {
              keySeparator: '>',
              nsSeparator: '>',
            })}
          </span>
          <span className="line-clamp-1 lg:group-hover:text-link-hover">
            {userCurrentGame.title}
          </span>
        </div>
      </div>

      <LuChevronRight className="size-4 min-w-4 transition lg:group-hover:translate-x-1" />
    </a>
  );
};
