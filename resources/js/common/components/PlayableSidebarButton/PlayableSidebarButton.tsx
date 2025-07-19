import type { FC, ReactNode } from 'react';
import type { IconType } from 'react-icons/lib';
import type { RequireAtLeastOne } from 'type-fest';

import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from '../+vendor/BaseButton';
import { BaseChip } from '../+vendor/BaseChip';
import { InertiaLink } from '../InertiaLink';

interface BasePlayableSidebarButtonProps {
  href: string;
  onClick: () => void;
  IconComponent: IconType;

  'aria-pressed'?: boolean;
  children?: ReactNode;
  className?: string;
  count?: number;
  isInertiaLink?: boolean;
  target?: string;
}

type PlayableSidebarButtonProps = RequireAtLeastOne<
  BasePlayableSidebarButtonProps & {
    href?: string;
    onClick?: () => void;
  },
  'href' | 'onClick'
>;

export const PlayableSidebarButton: FC<PlayableSidebarButtonProps> = ({
  children,
  className,
  count,
  href,
  IconComponent,
  isInertiaLink,
  onClick,
  target,
  ...rest // should only include aria attributes
}) => {
  const finalClassName = baseButtonVariants({
    className: cn('items-center justify-between gap-2', className),
  });

  if (onClick) {
    return (
      <button onClick={onClick} className={finalClassName} {...rest}>
        <ButtonContent count={count} IconComponent={IconComponent}>
          {children}
        </ButtonContent>
      </button>
    );
  }

  const Comp = isInertiaLink ? InertiaLink : 'a';

  return (
    <Comp
      href={href as string}
      className={finalClassName}
      prefetch={isInertiaLink ? 'desktop-hover-only' : undefined}
      target={target}
      {...rest}
    >
      <ButtonContent count={count} IconComponent={IconComponent}>
        {children}
      </ButtonContent>
    </Comp>
  );
};

type ButtonContentProps = Pick<PlayableSidebarButtonProps, 'children' | 'count' | 'IconComponent'>;

const ButtonContent: FC<ButtonContentProps> = ({ children, count, IconComponent }) => {
  return (
    <>
      <span className="flex items-center gap-2">
        <IconComponent className="size-4" />
        {children}
      </span>

      {count ? (
        <BaseChip className="bg-neutral-950 px-2 text-neutral-300 light:border-neutral-500 light:text-neutral-800">
          {count}
        </BaseChip>
      ) : null}
    </>
  );
};
