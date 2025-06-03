import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { SelectableChip } from '../../../../common/components/SelectableChip';
import { selectedSystemIdAtom } from '../../state/downloads.atoms';

interface SelectableSystemChipProps {
  system?: App.Platform.Data.System;
}

export const SelectableSystemChip: FC<SelectableSystemChipProps> = ({ system }) => {
  const { t } = useTranslation();

  const [selectedSystemId, setSelectedSystemId] = useAtom(selectedSystemIdAtom);

  return (
    <SelectableChip
      isSelected={system ? selectedSystemId === system.id : !selectedSystemId}
      onClick={() => setSelectedSystemId(system?.id)}
    >
      {system ? (
        <img src={system.iconUrl} alt={system.name} width={18} height={18} />
      ) : (
        <img src="/assets/images/system/unknown.png" width={18} height={18} alt="all" />
      )}

      {system?.nameShort ?? t('All Systems')}
    </SelectableChip>
  );
};
