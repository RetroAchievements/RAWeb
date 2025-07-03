import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseSelectAsync } from '@/common/components/+vendor/BaseSelectAsync';
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useUserSearchQuery } from '@/common/hooks/useUserSearchQuery';
import { buildUserAvatarUrl } from '@/common/utils/buildUserAvatarUrl';

import { PopulatedPlayerCompletions } from './PopulatedPlayerCompletions';
import { useSelectAutoWidth } from './useSelectAutoWidth';

interface PlayableCompareProgressProps {
  followedPlayerCompletions: App.Platform.Data.FollowedPlayerCompletion[];
  game: App.Platform.Data.Game;
  variant: 'game' | 'event';
}

export const PlayableCompareProgress: FC<PlayableCompareProgressProps> = ({
  followedPlayerCompletions,
  game,
  variant,
}) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const query = useUserSearchQuery();

  const { autoWidth, autoWidthContainerRef } = useSelectAutoWidth();

  const [userValue, setUserValue] = useState('');

  if (!auth?.user) {
    return null;
  }

  const handleUserSelect = (selectedDisplayName: string) => {
    setUserValue(selectedDisplayName);

    window.location.assign(
      route('game.compare-unlocks', { user: selectedDisplayName, game: game.id }),
    );
  };

  const canShowPlayerCompletions = !!followedPlayerCompletions.length;

  return (
    <div data-testid="compare-progress">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Compare Progress')}</h2>

      <div className="flex flex-col gap-3 rounded-lg bg-embed p-3 light:border light:border-neutral-200 light:bg-white">
        <div className="flex flex-col">
          {canShowPlayerCompletions ? (
            <PopulatedPlayerCompletions
              followedPlayerCompletions={followedPlayerCompletions}
              game={game}
            />
          ) : (
            <p>
              {variant === 'event'
                ? t('No one you follow has unlocked any achievements for this event.')
                : t('No one you follow has unlocked any achievements for this game.')}
            </p>
          )}
        </div>

        <BaseSeparator />

        <div className="w-full" ref={autoWidthContainerRef}>
          <BaseSelectAsync<App.Data.User>
            query={query}
            noResultsMessage={t('No users found.')}
            popoverPlaceholder={t('type a username...')}
            triggerClassName="w-full"
            value={userValue}
            onChange={handleUserSelect}
            width={autoWidth}
            placeholder={t('compare with any user...')}
            getOptionValue={(user) => user.displayName}
            getDisplayValue={(user) => (
              <div className="flex items-center gap-2">
                <img className="size-6 rounded-sm" src={buildUserAvatarUrl(user.avatarUrl)} />
                <span className="font-medium">{user.displayName}</span>
              </div>
            )}
            renderOption={(user) => (
              <div className="flex items-center gap-2">
                <img className="size-6 rounded-sm" src={buildUserAvatarUrl(user.avatarUrl)} />
                <span className="font-medium">{user.displayName}</span>
              </div>
            )}
          />
        </div>
      </div>
    </div>
  );
};
