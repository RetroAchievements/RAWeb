import type { FC } from 'react';
import type { IconType } from 'react-icons/lib';

import { cn } from '@/common/utils/cn';

interface ActivityBasicLabelProps {
  Icon: IconType;
  label: string;
  className?: string;
}

export const ActivityBasicLabel: FC<ActivityBasicLabelProps> = ({ className, Icon, label }) => {
  return (
    <div className={cn('flex items-center gap-1.5', className)}>
      <Icon className="size-4 min-w-5 light:text-neutral-400" />
      <span>{label}</span>
    </div>
  );
};
