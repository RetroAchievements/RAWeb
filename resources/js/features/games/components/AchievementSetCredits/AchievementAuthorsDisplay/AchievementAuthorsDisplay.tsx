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
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { TooltipCreditRow } from '../TooltipCreditRow';
import { TooltipCreditsSection } from '../TooltipCreditsSection';

interface AchievementAuthorsDisplayProps {
  authors: App.Platform.Data.UserCredits[];
}

export const AchievementAuthorsDisplay: FC<AchievementAuthorsDisplayProps> = ({ authors }) => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const totalAchievements = game.achievementsPublished ?? 0;

  const containerClassNames = cn(
    'flex items-center rounded-md bg-neutral-800/70 py-1 pr-2',
    'light:bg-white light:border light:border-amber-300',
  );

  // Calculate each author's contribution percentage.
  const authorsWithPercentage = authors.map((author) => ({
    ...author,
    percentage: totalAchievements > 0 ? (author.count / totalAchievements) * 100 : 0,
  }));

  // Separate authors who contributed at least 30% from the rest.
  const prominentAuthors = authorsWithPercentage.filter((author) => author.percentage >= 30);
  const otherAuthors = authorsWithPercentage.filter((author) => author.percentage < 30);

  // If no prominent authors (all have < 30%), show them all in a stack.
  if (prominentAuthors.length === 0) {
    return (
      <div className={containerClassNames}>
        <TooltippedTrophyIcon authors={authors} />
        <UserAvatarStack users={authors} maxVisible={999} size={20} isOverlappingAvatars={false} />
      </div>
    );
  }

  // If we have prominent authors, show them with labels and stack the rest.
  return (
    <div className={containerClassNames}>
      <TooltippedTrophyIcon authors={authors} />

      <div className="flex gap-2">
        {prominentAuthors.map((author, authorIndex) => (
          <Fragment key={`ach-set-author-${author.displayName}`}>
            <UserAvatar size={20} imgClassName="select-none rounded-full bg-embed" {...author} />

            {authorIndex !== prominentAuthors.length - 1 || otherAuthors.length > 0 ? (
              <span className="text-neutral-700">{'•'}</span>
            ) : null}
          </Fragment>
        ))}

        {otherAuthors.length > 0 ? (
          <UserAvatarStack
            users={otherAuthors}
            maxVisible={999}
            size={20}
            isOverlappingAvatars={false}
          />
        ) : null}
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
