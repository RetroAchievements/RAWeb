import { type FC, Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import { FaTrophy } from 'react-icons/fa';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatar } from '@/common/components/UserAvatar';
import { UserAvatarStack } from '@/common/components/UserAvatarStack';
import { cn } from '@/common/utils/cn';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface AchievementAuthorsDisplayProps {
  authors: App.Platform.Data.UserCredits[];
}

export const AchievementAuthorsDisplay: FC<AchievementAuthorsDisplayProps> = ({ authors }) => {
  const containerClassNames = cn(
    'flex items-center rounded-md bg-neutral-800/70 py-1 pr-2',
    'light:bg-white light:border light:border-amber-300',
  );

  // 4-6 authors - show avatar images and name labels for top 2, stack for the rest
  if (authors.length >= 4 && authors.length <= 6) {
    return (
      <div className={containerClassNames}>
        <TooltippedTrophyIcon authors={authors} />

        <div className="flex gap-2">
          <UserAvatar size={20} imgClassName="select-none rounded-full bg-embed" {...authors[0]} />
          <span className="text-neutral-700">{'•'}</span>
          <UserAvatar size={20} imgClassName="select-none rounded-full bg-embed" {...authors[1]} />
          <span className="text-neutral-700">{'•'}</span>

          <UserAvatarStack
            users={[...authors.slice(2, authors.length)]}
            maxVisible={999}
            size={20}
            isOverlappingAvatars={false}
          />
        </div>
      </div>
    );
  }

  // 7 or more authors, show a stack for all authors with the top 8 visible
  if (authors.length >= 7) {
    return (
      <div className={containerClassNames}>
        <TooltippedTrophyIcon authors={authors} />
        <UserAvatarStack users={authors} maxVisible={999} size={20} isOverlappingAvatars={false} />
      </div>
    );
  }

  // 1-3 authors, show avatar images and name labels for all
  return (
    <div className={containerClassNames}>
      <TooltippedTrophyIcon authors={authors} />

      <div className="flex gap-2">
        {authors.map((author, authorIndex) => (
          <Fragment key={`ach-set-author-${author.displayName}`}>
            <UserAvatar size={20} imgClassName="select-none rounded-full bg-embed" {...author} />

            {authorIndex !== authors.length - 1 ? (
              <span className="text-neutral-700">{'•'}</span>
            ) : null}
          </Fragment>
        ))}
      </div>
    </div>
  );
};

interface TooltippedTrophyIconProps {
  authors: App.Platform.Data.UserCredits[];
}

const TooltippedTrophyIcon: FC<TooltippedTrophyIconProps> = ({ authors }) => {
  const { t } = useTranslation();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="flex items-center gap-1.5 px-2 py-[2.25px]">
        <FaTrophy className="h-full text-yellow-500" />
        <span className="hidden pr-1 md:inline lg:hidden xl:inline">
          {t('{{val, number}} authors', { val: authors.length, count: authors.length })}
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="whitespace-nowrap">
        <TooltipCreditsSection headingLabel={t('Achievement Authors')}>
          {authors.map((credit) => (
            <TooltipCreditRow
              key={`tooltip-author-${credit.displayName}`}
              credit={credit}
              showAchievementCount={true}
            />
          ))}
        </TooltipCreditsSection>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
