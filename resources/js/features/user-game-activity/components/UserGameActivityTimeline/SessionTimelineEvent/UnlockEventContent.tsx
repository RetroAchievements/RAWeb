import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowRight } from 'react-icons/lu';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { cn } from '@/common/utils/cn';
import { getIsAchievementPublished } from '@/common/utils/getIsAchievementPublished';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';

interface UnlockEventContentProps {
  achievement: App.Platform.Data.Achievement;
  hardcore: boolean;
  hardcoreLater: boolean;
  sessionType: App.Enums.PlayerGameActivitySessionType;
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  when: string | null;
  whenPrevious: string | null;
}

export const UnlockEventContent: FC<UnlockEventContentProps> = ({
  achievement,
  hardcore,
  hardcoreLater,
  sessionType,
  previousEventKind,
  when,
  whenPrevious,
}) => {
  const isOfficialAchievement = getIsAchievementPublished(achievement);

  return (
    <AchievementAvatar
      {...achievement}
      imgClassName={cn(isOfficialAchievement ? null : 'grayscale')}
      showHardcoreUnlockBorder={hardcore}
      showPointsInTitle={true}
      size={32}
      sublabelSlot={
        <AchievementTimingLabel
          achievement={achievement}
          hardcore={hardcore}
          hardcoreLater={hardcoreLater}
          previousEventKind={previousEventKind}
          sessionType={sessionType}
          when={when}
          whenPrevious={whenPrevious}
        />
      }
    />
  );
};

interface AchievementTimingLabelProps {
  achievement: App.Platform.Data.Achievement;
  hardcore: boolean;
  hardcoreLater: boolean;
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  sessionType: App.Enums.PlayerGameActivitySessionType;
  when: string | null;
  whenPrevious: string | null;
}

const AchievementTimingLabel: FC<AchievementTimingLabelProps> = ({
  achievement,
  hardcore,
  hardcoreLater,
  previousEventKind,
  sessionType,
  when,
  whenPrevious,
}) => {
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();

  const isOfficialAchievement = getIsAchievementPublished(achievement);

  // How long ago was the previous event in the session compared to this one?
  const diffInSeconds = when && whenPrevious ? dayjs(when).diff(dayjs(whenPrevious), 'second') : 0;

  // This usually happens for reconstructed sessions, where we start stitching the session
  // together based on the first unlock time. That first unlock time is going to be seen
  // as "instantaneous", because it's where the reconstructed session is starting from.
  const isInstantSessionStart = previousEventKind === 'start-session' && diffInSeconds === 0;

  const getTimingMessage = (): string | null => {
    if (isInstantSessionStart && sessionType === 'reconstructed') {
      return t('Start of reconstructed timeline.');
    }

    if (previousEventKind === 'start-session') {
      return diffInSeconds > 0
        ? t('{{time}} after session start.', { time: formatDuration(diffInSeconds) })
        : null;
    }

    return diffInSeconds > 0
      ? t('{{time}} after previous.', { time: formatDuration(diffInSeconds) })
      : null;
  };

  const timingMessage = getTimingMessage();

  return (
    <span className="flex items-center gap-0.5 text-2xs text-neutral-500">
      {timingMessage !== null ? <LuArrowRight data-testid="arrow-icon" /> : null}

      <div className="flex items-center gap-1">
        {/* Main timing message, ie: "5m 20s after previous." */}
        {timingMessage}

        {/* Status indicators. */}
        {!hardcore ? <span className="text-neutral-500">{t('(Softcore)')}</span> : null}
        {!isOfficialAchievement ? (
          <span className="font-semibold text-neutral-500">{t('Unofficial Achievement.')}</span>
        ) : null}
        {hardcoreLater ? <span>{t('Unlocked later in hardcore.')}</span> : null}
      </div>
    </span>
  );
};
