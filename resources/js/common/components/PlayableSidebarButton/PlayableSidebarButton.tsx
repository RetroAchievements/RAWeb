import type { FC, ReactNode } from 'react';
import type { IconType } from 'react-icons/lib';

import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from '../+vendor/BaseButton';
import { BaseChip } from '../+vendor/BaseChip';
import { InertiaLink } from '../InertiaLink';

interface PlayableSidebarButtonProps {
  href: string;
  IconComponent: IconType;

  children?: ReactNode;
  className?: string;
  count?: number;
  isInertiaLink?: boolean;
  target?: string;
}

export const PlayableSidebarButton: FC<PlayableSidebarButtonProps> = ({
  children,
  className,
  count,
  href,
  IconComponent,
  isInertiaLink,
  target,
}) => {
  const Comp = isInertiaLink ? InertiaLink : 'a';

  return (
    <Comp
      href={href}
      className={baseButtonVariants({
        className: cn('items-center justify-between gap-2', className),
      })}
      prefetch={isInertiaLink ? 'desktop-hover-only' : undefined}
      target={target}
    >
      <span className="flex items-center gap-2">
        <IconComponent className="size-4" />
        {children}
      </span>

      {count ? (
        <BaseChip className="bg-neutral-950 px-2 text-neutral-300 light:border-neutral-500 light:text-neutral-800">
          {count}
        </BaseChip>
      ) : null}
    </Comp>
  );
};
