import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseCardContent,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { usePageProps } from '@/common/hooks/usePageProps';

import { selectedSystemIdAtom } from '../../state/downloads.atoms';
import { BrowseRemainingSystems } from './BrowseRemainingSystems';
import { SelectableSystemChip } from './SelectableSystemChip';
import { useSyncSystemQueryParam } from './useSyncSystemQueryParam';

export const SystemSelector: FC = () => {
  const { allSystems, topSystemIds } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  const selectedSystemId = useAtomValue(selectedSystemIdAtom);

  useSyncSystemQueryParam(selectedSystemId);

  // Get all visible systems and maintain the topSystemIds order.
  const allSystemsByPopularity = allSystems
    .filter((s) => topSystemIds.includes(s.id))
    .sort((a, b) => topSystemIds.indexOf(a.id) - topSystemIds.indexOf(b.id));

  // Get the top 3 systems so they can use a special placement.
  const top3Systems = allSystemsByPopularity.slice(0, 3);

  // Get the next 12 systems for the regular list, sorted alphabetically.
  const remainingSystems = allSystemsByPopularity.slice(3).slice(0, 12);
  const sortedSystems = remainingSystems.sort((a, b) => a.nameShort!.localeCompare(b.nameShort!));

  // This is only used by desktop to show a system chip after picking a system
  // from the all systems dialog.
  const visibleSystemIds = [...top3Systems.map((s) => s.id), ...sortedSystems.map((s) => s.id)];

  // Get the top 5 systems for the mobile-only view.
  const top5Systems = allSystemsByPopularity.slice(0, 5);

  return (
    <div>
      <BaseCardHeader className="pb-3">
        <BaseCardTitle className="text-lg">{t('Select a Gaming System')}</BaseCardTitle>
      </BaseCardHeader>

      <BaseCardContent>
        <div className="flex flex-col gap-5">
          {/* Mobile view */}
          <div className="flex flex-wrap gap-x-2 gap-y-1 sm:hidden">
            {/* Defaults to "All Systems" */}
            <SelectableSystemChip />

            {/* Top 5 systems, next to "All Systems" */}
            {top5Systems.map((system) => (
              <SelectableSystemChip key={`system-chip-${system.id}`} system={system} />
            ))}
          </div>

          {/* Desktop view */}
          <div className="hidden flex-wrap gap-x-2 gap-y-1 sm:flex">
            {/* Defaults to "All Systems" */}
            <SelectableSystemChip />

            {/* Top 3 systems, next to "All Systems" */}
            {top3Systems.map((system) => (
              <SelectableSystemChip key={`system-chip-${system.id}`} system={system} />
            ))}
          </div>

          <BaseSeparator className="hidden sm:block" />

          {/* Remaining systems (desktop only) */}
          {sortedSystems.length > 0 && (
            <div className="hidden flex-wrap gap-x-2 gap-y-1 sm:flex">
              {sortedSystems.map((system) => (
                <SelectableSystemChip key={`system-chip-${system.id}`} system={system} />
              ))}
            </div>
          )}

          <BrowseRemainingSystems visibleSystemIds={visibleSystemIds} />
        </div>
      </BaseCardContent>
    </div>
  );
};
