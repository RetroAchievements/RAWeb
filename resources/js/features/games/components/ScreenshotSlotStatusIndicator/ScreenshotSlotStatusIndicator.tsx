import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuTriangleAlert } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

interface ScreenshotSlotStatusIndicatorProps {
  typeStatus?: App.Platform.Data.ScreenshotUploadTypeStatus;
}

export const ScreenshotSlotStatusIndicator: FC<ScreenshotSlotStatusIndicatorProps> = ({
  typeStatus,
}) => {
  const { t } = useTranslation();

  if (!typeStatus) {
    return <span className="text-xs text-yellow-500/80">{t('Needed')}</span>;
  }

  if (typeStatus.hasResolutionIssues) {
    return (
      <BaseTooltip>
        <BaseTooltipTrigger asChild>
          <span role="img" aria-label="Warning">
            <LuTriangleAlert className="size-3.5 text-yellow-500" />
          </span>
        </BaseTooltipTrigger>

        <BaseTooltipPortal>
          <BaseTooltipContent>
            {t('Primary screenshot has an incorrect resolution')}
          </BaseTooltipContent>
        </BaseTooltipPortal>
      </BaseTooltip>
    );
  }

  return <LuCheck className="size-3.5 text-green-500" />;
};
