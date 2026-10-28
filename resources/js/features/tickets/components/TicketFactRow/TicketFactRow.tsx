import type { FC, ReactNode } from 'react';
import type { IconType } from 'react-icons/lib';

import { cn } from '@/common/utils/cn';

interface FactRowProps {
  children: ReactNode;
  Icon: IconType;

  iconClassName?: string;
}

export const TicketFactRow: FC<FactRowProps> = ({ children, Icon, iconClassName }) => {
  return (
    <li className="flex items-start gap-3 px-3 py-2">
      <Icon
        className={cn('mt-0.5 size-4 flex-none text-neutral-400', iconClassName)}
        aria-hidden="true"
      />

      <p>{children}</p>
    </li>
  );
};
