import type { FC } from 'react';
import type { IconType } from 'react-icons/lib';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface ActivityTooltipWrapperProps {
  Icon: IconType;
  label: string;
  t_tooltip: TranslatedString;

  className?: string;
}

export const ActivityTooltipWrapper: FC<ActivityTooltipWrapperProps> = ({
  className,
  Icon,
  label,
  t_tooltip,
}) => {
  return (
    <BaseTooltip>
      <BaseTooltipTrigger>
        <div className={cn('flex items-center gap-1.5', className)}>
          <Icon className="size-4 min-w-5 light:text-neutral-400" />
          <span>{label}</span>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="max-w-[320px]">
        <p className="text-xs">{t_tooltip}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
