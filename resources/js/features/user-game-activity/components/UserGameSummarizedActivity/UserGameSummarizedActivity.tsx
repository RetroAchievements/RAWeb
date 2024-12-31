import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';

import { ActivityStatCard } from './ActivityStatCard';
import { useFormatSessionsInfo } from './useFormatSessionInfo';

dayjs.extend(duration);

export const UserGameSummarizedActivity: FC = () => {
  const { activity, game, playerGame } =
    usePageProps<App.Platform.Data.PlayerGameActivityPageProps>();

  const { t } = useTranslation();

  const {
    achievementPlaytime,
    achievementSessionCount,
    generatedSessionAdjustment,
    totalPlaytime,
    totalUnlockTime,
  } = activity.summarizedActivity;

  const { formatSessionsInfo } = useFormatSessionsInfo();

  const { formatDuration } = useFormatDuration();

  return (
    <div className="grid gap-1 sm:grid-cols-2 xl:grid-cols-4 xl:gap-3">
      <ActivityStatCard
        t_label={t('Total Playtime')}
        t_tooltip={t(
          'All time the player spent in the game across all sessions, even when they did not earn achievements.',
        )}
      >
        {formatDuration(totalPlaytime)}
        {generatedSessionAdjustment !== 0 ? <EstimatedLabel /> : null}
      </ActivityStatCard>

      <ActivityStatCard
        t_label={t('Achievement Playtime')}
        t_tooltip={t('Only counts time from sessions where achievements were unlocked.')}
      >
        {formatDuration(achievementPlaytime)}
        {generatedSessionAdjustment !== 0 ? <EstimatedLabel /> : null}
      </ActivityStatCard>

      <ActivityStatCard
        t_label={t('Achievement Sessions')}
        t_tooltip={t('The count of sessions where achievements were unlocked.')}
      >
        <span className="text-base">
          {formatSessionsInfo({ totalUnlockTime, sessionCount: achievementSessionCount })}
        </span>
      </ActivityStatCard>

      <ActivityStatCard t_label={t('Achievements Unlocked')}>
        {t('{{earned, number}} of {{total, number}}', {
          earned: playerGame.achievementsUnlocked,
          total: game.achievementsPublished,
        })}
      </ActivityStatCard>
    </div>
  );
};

const EstimatedLabel: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <span className="ml-1 text-xs">{t('(estimated)')}</span>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="max-w-[280px]">
        <span className="text-xs">
          {t(
            'This player has sessions for the game where the playtime recorded to the server is not precise.',
          )}
        </span>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
