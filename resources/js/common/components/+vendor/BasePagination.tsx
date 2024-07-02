import { type ComponentProps, forwardRef } from 'react';
import { LuChevronLeft, LuChevronRight, LuMoreHorizontal } from 'react-icons/lu';

import { cn } from '@/utils/cn';

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
  isActive?: boolean;
} & Pick<BaseButtonProps, 'size'> &
  ComponentProps<'a'>;

const BasePaginationLink = ({
  className,
  isActive,
  size = 'icon',
  ...props
}: BasePaginationLinkProps) => (
  <a
    aria-current={isActive ? 'page' : undefined}
    className={cn(
      baseButtonVariants({
        variant: isActive ? 'outline' : 'ghost',
        size,
      }),
      'text-xs',
      className,
    )}
    {...props}
  />
);
BasePaginationLink.displayName = 'BasePaginationLink';

const BasePaginationPrevious = ({
  className,
  ...props
}: ComponentProps<typeof BasePaginationLink>) => (
  <BasePaginationLink size="default" className={cn('gap-1 pl-2.5', className)} {...props}>
    <LuChevronLeft className="h-4 w-4" />
    <span>{props.children ?? 'Previous'}</span>
  </BasePaginationLink>
);
BasePaginationPrevious.displayName = 'BasePaginationPrevious';

const BasePaginationNext = ({ className, ...props }: ComponentProps<typeof BasePaginationLink>) => (
  <BasePaginationLink size="default" className={cn('gap-1 pr-2.5', className)} {...props}>
    <span>{props.children ?? 'Next'}</span>
    <LuChevronRight className="h-4 w-4" />
  </BasePaginationLink>
);
BasePaginationNext.displayName = 'BasePaginationNext';

const BasePaginationEllipsis = ({ className, ...props }: ComponentProps<'span'>) => (
  <span
    aria-hidden
    className={cn('flex h-9 w-9 items-center justify-center', className)}
    {...props}
  >
    <LuMoreHorizontal className="h-4 w-4" />
    <span className="sr-only">More pages</span>
  </span>
);
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
