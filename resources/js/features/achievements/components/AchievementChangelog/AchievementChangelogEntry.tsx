import type { TFunction } from 'i18next';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuInfo } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatar } from '@/common/components/UserAvatar';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { cn } from '@/common/utils/cn';

import { FIELD_LEVEL_TRACKING_CUTOFF } from '../../utils/fieldLevelTrackingCutoff';

interface AchievementChangelogEntryProps {
  entry: App.Platform.Data.AchievementChangelogEntry;
  isCreatedAsPromoted?: boolean;
}

export const AchievementChangelogEntry: FC<AchievementChangelogEntryProps> = ({
  entry,
  isCreatedAsPromoted,
}) => {
  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const header = buildHeader(entry, t);

  return (
    <li className="group relative flex gap-3 pb-6 last:pb-0" data-testid="changelog-entry">
      {/* Connects this entry's dot to the next entry below. */}
      <div className="absolute -bottom-1 left-[3px] top-3 w-px bg-neutral-700 group-last:hidden" />

      {/* Color-coded dot indicating the nature of this changelog event. */}
      <div
        data-testid="changelog-dot"
        className={cn(
          'relative mt-1.5 h-2 w-2 shrink-0 rounded-full',
          getDotColor(entry.type, isCreatedAsPromoted),
        )}
      />

      <div className="flex min-w-0 flex-col gap-0.5">
        <p className="flex items-center gap-1 text-xs">
          {header}

          {entry.count > 1 && entry.type === 'edited' ? (
            <span className="text-neutral-400">
              {t('({{count}} times)', { count: entry.count })}
            </span>
          ) : null}

          {entry.type === 'edited' && new Date(entry.createdAt) < FIELD_LEVEL_TRACKING_CUTOFF ? (
            <BaseTooltip>
              <BaseTooltipTrigger>
                <LuInfo className="size-3.5 text-neutral-500 transition hover:text-neutral-300 light:text-neutral-700" />
              </BaseTooltipTrigger>

              <BaseTooltipContent className="max-w-72 font-normal leading-normal">
                <span className="text-xs">
                  {t('Detailed change tracking was not available before April 2022.')}
                </span>
              </BaseTooltipContent>
            </BaseTooltip>
          ) : null}
        </p>

        {entry.fieldChanges.length > 0 && !hasInlineFieldChanges(entry.type) ? (
          <div className="flex flex-col gap-0.5">
            {entry.fieldChanges.map((change, index) => (
              <div key={`change-${index}`} className="flex flex-col gap-0.5 text-2xs">
                {change.oldValue !== null ? (
                  <span className="rounded bg-red-950/40 px-1 py-px text-red-400 line-through light:bg-red-100 light:text-red-700">
                    {change.oldValue}
                  </span>
                ) : null}

                {change.newValue !== null ? (
                  <span className="rounded bg-green-950/40 px-1 py-px text-green-400 light:bg-green-100 light:text-green-700">
                    {change.newValue}
                  </span>
                ) : null}
              </div>
            ))}
          </div>
        ) : null}

        <div className="flex items-center gap-1.5 text-2xs">
          {entry.user ? <UserAvatar {...entry.user} size={16} showLabel={true} /> : null}

          <span className="text-neutral-500">{formatDate(entry.createdAt, 'll')}</span>
        </div>
      </div>
    </li>
  );
};

type EntryType = App.Platform.Data.AchievementChangelogEntry['type'];

/**
 * Avoid rendering a separate diff block for types that already show values inline.
 */
function hasInlineFieldChanges(type: EntryType): boolean {
  return type === 'type-set' || type === 'points-changed' || type === 'moved-to-different-game';
}

function getDotColor(type: EntryType, isCreatedAsPromoted?: boolean): string {
  switch (type) {
    case 'created':
      return isCreatedAsPromoted ? 'bg-green-500' : 'bg-blue-500';

    case 'deleted':
    case 'demoted':
      return 'bg-red-500';

    case 'restored':
      return 'bg-blue-500';

    case 'promoted':
      return 'bg-green-500';

    default:
      return 'bg-neutral-600';
  }
}

function buildHeader(entry: App.Platform.Data.AchievementChangelogEntry, t: TFunction): string {
  switch (entry.type) {
    case 'created':
      return t('Created');

    case 'deleted':
      return t('Deleted');

    case 'restored':
      return t('Restored');

    case 'edited':
      return t('Edited');

    case 'promoted':
      return t('Promoted');

    case 'demoted':
      return t('Demoted');

    case 'description-updated':
      return t('Description updated');

    case 'title-updated':
      return t('Title updated');

    case 'points-changed': {
      const oldVal = entry.fieldChanges[0]?.oldValue;
      const newVal = entry.fieldChanges[0]?.newValue;

      return oldVal && newVal
        ? t('Points changed from {{oldVal}} to {{newVal}}', { oldVal, newVal })
        : t('Points changed');
    }

    case 'badge-updated':
      return t('Badge updated');

    case 'embed-url-updated':
      return t('Embed URL updated');

    case 'logic-updated':
      return t('Logic updated');

    case 'moved-to-different-game': {
      const fromGame = entry.fieldChanges[0]?.oldValue;
      const toGame = entry.fieldChanges[0]?.newValue;

      return fromGame && toGame
        ? t('Transferred from {{fromGame}} to {{toGame}}', { fromGame, toGame })
        : t('Transferred to a different achievement set');
    }

    case 'type-set': {
      const typeName = entry.fieldChanges[0]?.newValue;

      return typeName ? t('Type set to {{typeName}}', { typeName }) : t('Type set');
    }

    case 'type-changed':
      return t('Type changed');

    case 'type-removed':
      return t('Type removed');

    default:
      return t('Edited');
  }
}
