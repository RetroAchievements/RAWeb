import { type ComponentProps, forwardRef } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronLeft, LuChevronRight, LuEllipsis } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

import { InertiaLink } from '../InertiaLink';
import { type BaseButtonProps, baseButtonVariants } from './BaseButton';

const BasePagination = ({ className, ...props }: ComponentProps<'nav'>) => (
  <nav
    role="navigation"
    aria-label="pagination"
    className={cn('bg-transparent', className)}
    {...props}
  />
);
BasePagination.displayName = 'BasePagination';

const BasePaginationContent = forwardRef<HTMLUListElement, ComponentProps<'ul'>>(
  ({ className, ...props }, ref) => (
    <ul ref={ref} className={cn('flex flex-row items-center gap-1', className)} {...props} />
  ),
);
BasePaginationContent.displayName = 'BasePaginationContent';

const BasePaginationItem = forwardRef<HTMLLIElement, ComponentProps<'li'>>(
  ({ className, ...props }, ref) => <li ref={ref} className={cn('', className)} {...props} />,
);
BasePaginationItem.displayName = 'BasePaginationItem';

type BasePaginationLinkProps = {
  href: string;
  isActive?: boolean;
} & Pick<BaseButtonProps, 'size'> &
  ComponentProps<'a'>;

const BasePaginationLink = ({
  className,
  isActive,
  size = 'icon',
  href,
  ...props
}: BasePaginationLinkProps) => (
  <InertiaLink
    href={href}
    className={cn(
      baseButtonVariants({
        variant: isActive ? 'outline' : 'ghost',
        size,
      }),
      'text-xs',
      className,
    )}
    aria-disabled={props['aria-disabled']}
  >
    {props.children}
  </InertiaLink>
);
BasePaginationLink.displayName = 'BasePaginationLink';

const BasePaginationPrevious = ({
  className,
  ...props
}: ComponentProps<typeof BasePaginationLink>) => (
  <BasePaginationLink size="default" className={cn('gap-1 pl-2.5', className)} {...props}>
    <LuChevronLeft className="size-4" />
    <span>{props.children ?? 'Previous'}</span>
  </BasePaginationLink>
);
BasePaginationPrevious.displayName = 'BasePaginationPrevious';

const BasePaginationNext = ({ className, ...props }: ComponentProps<typeof BasePaginationLink>) => (
  <BasePaginationLink size="default" className={cn('gap-1 pr-2.5', className)} {...props}>
    <span>{props.children ?? 'Next'}</span>
    <LuChevronRight className="size-4" />
  </BasePaginationLink>
);
BasePaginationNext.displayName = 'BasePaginationNext';

const BasePaginationEllipsis = ({ className, ...props }: ComponentProps<'span'>) => {
  const { t } = useTranslation();

  return (
    <span
      aria-hidden
      className={cn('flex h-9 w-9 items-center justify-center', className)}
      {...props}
    >
      <LuEllipsis className="size-4" />
      <span className="sr-only">{t('More pages')}</span>
    </span>
  );
};
BasePaginationEllipsis.displayName = 'BasePaginationEllipsis';

export {
  BasePagination,
  BasePaginationContent,
  BasePaginationEllipsis,
  BasePaginationItem,
  BasePaginationLink,
  BasePaginationNext,
  BasePaginationPrevious,
};
