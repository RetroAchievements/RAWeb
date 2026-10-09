import type { FC, ReactNode } from 'react';
import type { IconType } from 'react-icons/lib';

interface FactRowProps {
  children: ReactNode;
  Icon: IconType;
}

export const TicketFactRow: FC<FactRowProps> = ({ children, Icon }) => {
  return (
    <li className="flex items-start gap-3 px-3 py-2">
      <Icon className="mt-0.5 size-4 flex-none text-neutral-400" aria-hidden="true" />
      <p>{children}</p>
    </li>
  );
};
