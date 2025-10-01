import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCrown } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCurrentListView } from '../../hooks/useCurrentListView';

interface FeaturedLeaderboardsListProps {
  featuredLeaderboards: App.Platform.Data.Leaderboard[];
}

export const FeaturedLeaderboardsList: FC<FeaturedLeaderboardsListProps> = ({
  featuredLeaderboards,
}) => {
  const { numLeaderboards } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { setCurrentListView } = useCurrentListView();

  if (!featuredLeaderboards.length) {
    return null;
  }

  const handleViewAllLeaderboardsClick = () => {
    const targetElement = document.getElementById('game-achievement-sets-container');
    const yOffset = targetElement
      ? targetElement.getBoundingClientRect().top + window.scrollY - 48
      : 0;

    window.scrollTo({
      top: yOffset,
      behavior: 'smooth',
    });

    setTimeout(() => {
      setCurrentListView('leaderboards');
    }, 1000);
  };

  return (
    <div data-testid="featured-leaderboards-list">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Leaderboards')}</h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <ul className="zebra-list overflow-hidden rounded-lg">
          {featuredLeaderboards.map((leaderboard) => (
            <li
              key={`featured-lb-${leaderboard.id}`}
              className="game-set-item first:rounded-t-lg last:rounded-b-lg"
            >
              <div className="w-full gap-x-5 gap-y-1.5 pb-2.5 leading-4">
                <div className="sm:col-span-4 md:mt-1">
                  {/* Title */}
                  <div className="mb-0.5 md:mt-0">
                    <span className="mr-2">
                      <a href={`/leaderboardinfo.php?i=${leaderboard.id}`} className="font-medium">
                        {leaderboard.title}
                      </a>
                    </span>
                  </div>

                  {/* Description */}
                  <p className="leading-4">{leaderboard.description}</p>

                  {/* Top entry */}
                  <div className="mt-2.5">
                    {leaderboard.topEntry?.user ? (
                      <div className="flex items-center gap-3">
                        <LuCrown className="size-4 text-yellow-400 light:text-amber-600" />

                        <span className="text-neutral-300 light:text-neutral-700">
                          {leaderboard.topEntry.formattedScore}
                        </span>

                        <UserAvatar
                          {...leaderboard.topEntry.user}
                          size={16}
                          labelClassName="-ml-0.5"
                        />
                      </div>
                    ) : null}
                  </div>
                </div>
              </div>
            </li>
          ))}
        </ul>

        {numLeaderboards > 5 ? (
          <BaseButton
            size="sm"
            className="w-full justify-center border-none"
            onClick={handleViewAllLeaderboardsClick}
          >
            {t('View all {{val, number}} leaderboards', { val: numLeaderboards })}
          </BaseButton>
        ) : null}
      </div>
    </div>
  );
};
