import { useAtom } from 'jotai';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleCheckBig } from 'react-icons/lu';

import {
  BaseCardContent,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { usePageProps } from '@/common/hooks/usePageProps';

import { selectedPlatformIdAtom } from '../../state/downloads.atoms';
import { environmentIconMap } from '../../utils/environmentIconMap';
import { SelectableChip } from '../SelectableChip';

export const PlatformSelector: FC = () => {
  const { allPlatforms, userDetectedPlatformId } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  const [selectedPlatformId, setSelectedPlatformId] = useAtom(selectedPlatformIdAtom);

  const [wasPlatformChanged, setWasPlatformChanged] = useState(false);

  const handleSelectPlatform = (platformId?: number) => {
    setWasPlatformChanged(true);
    setSelectedPlatformId(platformId);
  };

  const visiblePlatforms = allPlatforms.filter((p) => p.orderColumn >= 0);
  const sortedPlatforms = visiblePlatforms.sort((a, b) => a.orderColumn - b.orderColumn);

  const userDetectedPlatform = visiblePlatforms.find((p) => p.id === userDetectedPlatformId);
  const selectedPlatform = visiblePlatforms.find((p) => p.id === selectedPlatformId);

  return (
    <div>
      <BaseCardHeader className="pb-3">
        <BaseCardTitle className="flex flex-col gap-2 text-sm sm:flex-row sm:items-center sm:gap-4">
          <span className="text-lg">{t('Select a Platform')}</span>

          {userDetectedPlatformId && selectedPlatform && !wasPlatformChanged ? (
            <div className="hidden items-center gap-1 text-xs font-normal tracking-normal text-green-500 sm:flex">
              <LuCircleCheckBig className="size-4" />
              <p>{t('{{platformName}} detected', { platformName: selectedPlatform.name })}</p>
            </div>
          ) : null}
        </BaseCardTitle>
      </BaseCardHeader>

      <BaseCardContent>
        <div className="flex flex-col gap-5">
          <div className="flex gap-x-2 gap-y-1">
            <SelectableChip
              isSelected={!selectedPlatformId}
              onClick={() => handleSelectPlatform(undefined)}
            >
              <img src="/assets/images/system/unknown.png" width={18} height={18} alt="all" />
              {t('All Platforms')}
            </SelectableChip>

            {userDetectedPlatform ? (
              <div className="sm:hidden">
                <SelectableChip
                  isSelected={selectedPlatformId === userDetectedPlatform.id}
                  onClick={() => handleSelectPlatform(userDetectedPlatform.id)}
                >
                  {userDetectedPlatform.executionEnvironment
                    ? environmentIconMap[userDetectedPlatform.executionEnvironment]
                    : null}
                  {userDetectedPlatform.name}
                </SelectableChip>
              </div>
            ) : null}
          </div>

          <BaseSeparator className="hidden sm:block" />

          <div className="hidden flex-wrap gap-x-2 gap-y-1 sm:flex">
            {sortedPlatforms.map((platform) => (
              <SelectableChip
                key={`platform-chip-${platform.id}`}
                isSelected={selectedPlatformId === platform.id}
                onClick={() => handleSelectPlatform(platform.id)}
              >
                {platform.executionEnvironment
                  ? environmentIconMap[platform.executionEnvironment]
                  : null}
                {platform.name}
              </SelectableChip>
            ))}
          </div>
        </div>
      </BaseCardContent>
    </div>
  );
};
