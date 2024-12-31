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
  previousEventKind: App.Enums.PlayerGameActivityEventType | 'start-session';
  when: string | null;
  whenPrevious: string | null;
}

export const UnlockEventContent: FC<UnlockEventContentProps> = ({
  achievement,
  hardcore,
  hardcoreLater,
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
      sublabelSlot={
        <AchievementTimingLabel
          achievement={achievement}
          hardcore={hardcore}
          hardcoreLater={hardcoreLater}
          previousEventKind={previousEventKind}
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
  when: string | null;
  whenPrevious: string | null;
}

const AchievementTimingLabel: FC<AchievementTimingLabelProps> = ({
  achievement,
  hardcore,
  hardcoreLater,
  previousEventKind,
  when,
  whenPrevious,
}) => {
  const { t } = useTranslation();

  const { formatDuration } = useFormatDuration();

  const isOfficialAchievement = getIsAchievementPublished(achievement);

  const diffInSeconds = when && whenPrevious ? dayjs(when).diff(dayjs(whenPrevious), 'second') : 0;

  // No label is needed for instant achievements at session start.
  if (!hardcoreLater && previousEventKind === 'start-session' && diffInSeconds === 0) {
    return null;
  }

  return (
    <span className="flex items-center gap-0.5 text-2xs text-neutral-500">
      <LuArrowRight />

      {!hardcore ? <span className="mr-1 text-neutral-500">{t('(Softcore)')}</span> : null}

      {!isOfficialAchievement ? (
        <span className="mr-1 font-semibold text-neutral-500">{t('Unofficial Achievement')}</span>
      ) : null}

      {hardcoreLater ? t('Unlocked later in hardcore') : null}

      {previousEventKind === 'start-session' && diffInSeconds > 0
        ? t('{{time}} after session start', {
            time: formatDuration(diffInSeconds),
          })
        : null}

      {previousEventKind !== 'start-session' &&
        (diffInSeconds > 0
          ? t('{{time}} after previous', {
              time: formatDuration(diffInSeconds),
            })
          : t('Immediately after previous'))}
    </span>
  );
};
