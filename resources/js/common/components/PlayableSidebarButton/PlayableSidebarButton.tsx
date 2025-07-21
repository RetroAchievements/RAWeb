import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';
import { LuLayers } from 'react-icons/lu';
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
    showSubsetIndicator?: boolean;
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
  showSubsetIndicator = false,
  ...rest // should only include aria attributes
}) => {
  const finalClassName = baseButtonVariants({
    className: cn('items-center justify-between gap-2 relative overflow-hidden', className),
  });

  if (onClick) {
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
  const { t } = useTranslation();

  return (
    <>
      <span className="flex items-center gap-2">
        <span className="flex items-center gap-0.5">
          {showSubsetIndicator ? (
            <>
              <LuLayers
                role="img"
                className="size-4"
                title={t('Subset')}
                aria-label={t('Subset')}
              />
              <span className="sr-only">{t('Subset')}</span>
            </>
          ) : null}

          <IconComponent className="size-4" />
        </span>

        <span className="flex items-center gap-1">{children}</span>
      </span>

      {count ? (
        <BaseChip className="bg-neutral-950 px-2 text-neutral-300 light:border-neutral-500 light:text-neutral-800">
          {count}
        </BaseChip>
      ) : null}
    </>
  );
};
