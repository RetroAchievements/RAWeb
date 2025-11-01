import type { FC, ReactNode } from 'react';
import type { IconType } from 'react-icons/lib';

import { SubsetIcon } from '@/common/components/SubsetIcon';
import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from '../+vendor/BaseButton';
import { BaseChip } from '../+vendor/BaseChip';
import { InertiaLink } from '../InertiaLink';

interface BasePlayableSidebarButtonProps {
  IconComponent: IconType;

  'aria-pressed'?: boolean;
  children?: ReactNode;
  className?: string;
  count?: number;
  href?: string;
  isInertiaLink?: boolean;
  onClick?: () => void;
  target?: string;
}

type PlayableSidebarButtonProps = BasePlayableSidebarButtonProps & {
  href?: string;
  onClick?: () => void;
  showSubsetIndicator?: boolean;
};

export const PlayableSidebarButton: FC<PlayableSidebarButtonProps> = ({
  children,
  className,
  count,
  href,
  IconComponent,
  isInertiaLink,
  onClick,
  target,
  showSubsetIndicator = false,
  ...rest // should only include aria attributes
}) => {
  const finalClassName = baseButtonVariants({
    className: cn('items-center justify-between gap-2 relative overflow-hidden', className),
  });

  if (onClick && !href) {
    return (
      <button onClick={onClick} className={finalClassName} {...rest}>
        <ButtonContent
          count={count}
          IconComponent={IconComponent}
          showSubsetIndicator={showSubsetIndicator}
        >
          {children}
        </ButtonContent>
      </button>
    );
  }

  const Comp = isInertiaLink ? InertiaLink : 'a';

  return (
    <Comp
      href={href as string}
      onClick={onClick}
      className={finalClassName}
      prefetch={isInertiaLink ? 'desktop-hover-only' : undefined}
      target={target}
      {...rest}
    >
      <ButtonContent
        count={count}
        IconComponent={IconComponent}
        showSubsetIndicator={showSubsetIndicator}
      >
        {children}
      </ButtonContent>
    </Comp>
  );
};

type ButtonContentProps = Pick<
  PlayableSidebarButtonProps,
  'children' | 'count' | 'IconComponent' | 'showSubsetIndicator'
>;

const ButtonContent: FC<ButtonContentProps> = ({
  children,
  count,
  IconComponent,
  showSubsetIndicator,
}) => {
  return (
    <>
      <span className="flex items-center gap-2">
        <span className="flex items-center gap-0.5">
          <IconComponent className="size-4" />

          {showSubsetIndicator ? <SubsetIcon /> : null}
        </span>

        <span className="flex items-center gap-1">{children}</span>
      </span>

      {count ? (
        <BaseChip className="border border-neutral-700 bg-neutral-900 px-2 text-2xs text-neutral-300 light:border-neutral-500 light:text-neutral-800">
          {count}
        </BaseChip>
      ) : null}
    </>
  );
};
