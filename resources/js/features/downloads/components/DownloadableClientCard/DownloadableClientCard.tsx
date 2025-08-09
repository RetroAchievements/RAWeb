import type { FC } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleAlert } from 'react-icons/lu';

import {
  BaseCard,
  BaseCardContent,
  BaseCardFooter,
  BaseCardHeader,
  BaseCardTitle,
} from '@/common/components/+vendor/BaseCard';
import { BaseChip } from '@/common/components/+vendor/BaseChip';
import {
  BasePopover,
  BasePopoverContent,
  BasePopoverTrigger,
} from '@/common/components/+vendor/BasePopover';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { SystemChip } from '@/common/components/SystemChip';
import { usePageProps } from '@/common/hooks/usePageProps';

import { environmentIconMap } from '../../utils/environmentIconMap';
import { DownloadLinks } from './DownloadLinks';
import { MetadataLinks } from './MetadataLinks';

interface DownloadableClientCardProps {
  emulator: App.Platform.Data.Emulator;
}

export const DownloadableClientCard: FC<DownloadableClientCardProps> = ({ emulator }) => {
  const { topSystemIds } = usePageProps<App.Http.Data.DownloadsPageProps>();
  const { t } = useTranslation();

  const [isPopoverOpen, setIsPopoverOpen] = useState(false);

  if (!emulator.systems) {
    return null;
  }

  const visiblePlatforms = (emulator.platforms as App.Platform.Data.Platform[]).filter(
    (p) => p.orderColumn >= 0,
  );
  const sortedPlatforms = visiblePlatforms.sort((a, b) => a.orderColumn - b.orderColumn);

  const systems = emulator.systems.sort(
    (a, b) => topSystemIds.indexOf(a.id) - topSystemIds.indexOf(b.id),
  );

  const cardSystems = systems.slice(0, 8).sort((a, b) => a.nameShort!.localeCompare(b.nameShort!));
  const tooltipSystems = systems.slice(8).sort((a, b) => a.name!.localeCompare(b.name!));

  return (
    <BaseCard
      data-testid="downloadable-client"
      className="flex h-full flex-col light:bg-white light:shadow-sm"
    >
      <BaseCardHeader>
        <BaseCardTitle className="flex items-center gap-2 text-xl">
          <span>{emulator.name}</span>

          {!emulator.canDebugTriggers ? (
            <BasePopover open={isPopoverOpen}>
              <BasePopoverTrigger
                onMouseEnter={() => setIsPopoverOpen(true)}
                onMouseLeave={() => setIsPopoverOpen(false)}
                onClick={(e) => e.preventDefault()}
              >
                <LuCircleAlert data-testid="warning-icon" className="text-neutral-500" />
              </BasePopoverTrigger>

              <BasePopoverContent
                onMouseEnter={() => setIsPopoverOpen(true)}
                onMouseLeave={() => setIsPopoverOpen(false)}
                onPointerDownOutside={(e) => e.preventDefault()}
                onInteractOutside={(e) => e.preventDefault()}
              >
                {t(
                  'Developers may not be able to easily resolve tickets submitted from this emulator.',
                )}
              </BasePopoverContent>
            </BasePopover>
          ) : null}
        </BaseCardTitle>
      </BaseCardHeader>

      <BaseCardContent className="flex flex-grow flex-col gap-8">
        <div className="flex flex-col gap-1">
          <p className="tracking-wide text-neutral-400 light:text-neutral-700">
            {t('supportedSystemsCountLabel', { count: systems.length, val: systems.length })}
          </p>

          <div className="flex flex-wrap items-center gap-1">
            {cardSystems.map((system) => (
              <BaseTooltip key={`${emulator.id}-${system.id}`}>
                <BaseTooltipTrigger>
                  <SystemChip {...system} />
                </BaseTooltipTrigger>

                <BaseTooltipContent>{system.name}</BaseTooltipContent>
              </BaseTooltip>
            ))}

            {tooltipSystems.length ? (
              <BaseTooltip>
                <BaseTooltipTrigger>
                  <p className="text-neutral-400 underline decoration-dotted light:text-neutral-700">
                    {t('+{{val, number}} more', { val: tooltipSystems.length })}
                  </p>
                </BaseTooltipTrigger>

                <BaseTooltipContent>
                  <span className="flex max-w-[300px] flex-wrap gap-x-1 gap-y-1.5 py-2 lg:max-w-[500px]">
                    {tooltipSystems.map((system) => (
                      <SystemChip
                        key={`${emulator.id}-${system.id}`}
                        {...system}
                        className="bg-neutral-800"
                      >
                        {system.name}
                      </SystemChip>
                    ))}
                  </span>
                </BaseTooltipContent>
              </BaseTooltip>
            ) : null}
          </div>
        </div>

        <div className="flex flex-col gap-1">
          <p className="tracking-wide text-neutral-400 light:text-neutral-700">
            {t('Available on')}
          </p>

          <div className="flex flex-wrap items-center gap-1">
            {sortedPlatforms.map((platform) => (
              <BaseChip
                key={`card-${platform.id}`}
                className="text-neutral-300 light:text-neutral-700"
              >
                {platform.executionEnvironment
                  ? environmentIconMap[platform.executionEnvironment]
                  : null}
                {platform.name}
              </BaseChip>
            ))}
          </div>
        </div>
      </BaseCardContent>

      <BaseCardFooter className="flex flex-col gap-3">
        <DownloadLinks emulator={emulator} />
        <MetadataLinks emulator={emulator} />
      </BaseCardFooter>
    </BaseCard>
  );
};
