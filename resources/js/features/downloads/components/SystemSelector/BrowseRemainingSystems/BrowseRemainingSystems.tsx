import { useAtomValue, useSetAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { RxMagnifyingGlass } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import {
  isAllSystemsDialogOpenAtom,
  selectedSystemIdAtom,
} from '@/features/downloads/state/downloads.atoms';

import { SelectableChip } from '../../SelectableChip';

interface BrowseRemainingSystemsProps {
  visibleSystemIds: number[];
}

export const BrowseRemainingSystems: FC<BrowseRemainingSystemsProps> = ({ visibleSystemIds }) => {
  const { allSystems } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  const selectedSystemId = useAtomValue(selectedSystemIdAtom);
  const setIsAllSystemsDialogOpen = useSetAtom(isAllSystemsDialogOpenAtom);

  if (!allSystems?.length) {
    return null;
  }

  const selectedSystem = allSystems.find((s) => s.id === selectedSystemId);

  return (
    <div className="flex items-center gap-2">
      <BaseButton
        size="sm"
        className="flex w-full items-center gap-1.5 sm:max-w-fit"
        onClick={() => setIsAllSystemsDialogOpen(true)}
      >
        <RxMagnifyingGlass className="size-4" />{' '}
        {t('Browse all {{systemCount, number}} systems', {
          systemCount: allSystems.length,
        })}
      </BaseButton>

      {selectedSystemId && selectedSystem && !visibleSystemIds.includes(selectedSystemId) ? (
        <SelectableChip isSelected={true}>
          <img src={selectedSystem.iconUrl} alt={selectedSystem.name} width={18} height={18} />
          {selectedSystem?.nameShort}
        </SelectableChip>
      ) : null}
    </div>
  );
};
