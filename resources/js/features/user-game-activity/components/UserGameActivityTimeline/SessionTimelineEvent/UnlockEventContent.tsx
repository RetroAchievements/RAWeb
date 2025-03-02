import dayjs from 'dayjs';
import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuArrowRight } from 'react-icons/lu';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { UserAvatar } from '@/common/components/UserAvatar';
import { cn } from '@/common/utils/cn';
import { getIsAchievementPublished } from '@/common/utils/getIsAchievementPublished';
import { useFormatDuration } from '@/common/utils/l10n/useFormatDuration';

interface UnlockEventContentProps {
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  sessionEvent: App.Platform.Data.PlayerGameActivityEvent;
  sessionType: App.Enums.PlayerGameActivitySessionType;
  whenPrevious: string | null;
}

export const UnlockEventContent: FC<UnlockEventContentProps> = ({
  previousEventKind,
  sessionEvent,
  sessionType,
  whenPrevious,
}) => {
  const achievement = sessionEvent.achievement as App.Platform.Data.Achievement;

  const isOfficialAchievement = getIsAchievementPublished(achievement);

  return (
    <AchievementAvatar
      {...achievement}
      imgClassName={cn(isOfficialAchievement ? null : 'grayscale')}
      displayLockedStatus={sessionEvent.hardcore ? 'unlocked-hardcore' : 'locked'}
      showPointsInTitle={true}
      size={32}
      sublabelSlot={
        <AchievementTimingLabel
          previousEventKind={previousEventKind}
          sessionEvent={sessionEvent}
          sessionType={sessionType}
          whenPrevious={whenPrevious}
        />
      }
    />
  );
};

interface AchievementTimingLabelProps {
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  sessionEvent: App.Platform.Data.PlayerGameActivityEvent;
  sessionType: App.Enums.PlayerGameActivitySessionType;
  whenPrevious: string | null;
}

const AchievementTimingLabel: FC<AchievementTimingLabelProps> = ({
  previousEventKind,
  sessionEvent,
  sessionType,
  whenPrevious,
}) => {
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();

  const achievement = sessionEvent.achievement as App.Platform.Data.Achievement;
  const isOfficialAchievement = getIsAchievementPublished(achievement);

  // How long ago was the previous event in the session compared to this one?
  const diffInSeconds =
    sessionEvent.when && whenPrevious
      ? dayjs(sessionEvent.when).diff(dayjs(whenPrevious), 'second')
      : 0;

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
        {sessionEvent.unlocker ? (
          <span className="flex items-center font-semibold">
            <Trans
              i18nKey="Manually unlocked by <1>{{user}}</1>."
              components={{
                1: (
                  <UserAvatar
                    {...sessionEvent.unlocker}
                    size={16}
                    wrapperClassName="ml-1"
                    imgClassName="-mr-1"
                  />
                ),
              }}
            />
          </span>
        ) : null}

        {!sessionEvent.hardcore ? (
          <span className="text-neutral-500">{t('(Softcore)')}</span>
        ) : null}

        {!isOfficialAchievement ? (
          <span className="font-semibold text-neutral-500">{t('Unofficial Achievement.')}</span>
        ) : null}

        {sessionEvent.hardcoreLater ? <span>{t('Unlocked later in hardcore.')}</span> : null}
      </div>
    </span>
  );
};
