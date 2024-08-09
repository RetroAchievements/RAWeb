import { Slot } from '@radix-ui/react-slot';
import {
  type ComponentProps,
  type ComponentPropsWithoutRef,
  forwardRef,
  type ReactNode,
} from 'react';
import { LuChevronRight, LuMoreHorizontal } from 'react-icons/lu';

import { cn } from '@/utils/cn';

const BaseBreadcrumb = forwardRef<
  HTMLElement,
  ComponentPropsWithoutRef<'nav'> & {
    separator?: ReactNode;
  }
>(({ ...props }, ref) => <nav ref={ref} aria-label="breadcrumb" {...props} />);
BaseBreadcrumb.displayName = 'BaseBreadcrumb';

const BaseBreadcrumbList = forwardRef<HTMLOListElement, ComponentPropsWithoutRef<'ol'>>(
  ({ className, ...props }, ref) => (
    <ol
      ref={ref}
      className={cn(
        'flex flex-wrap items-center gap-1.5 break-words text-xs text-neutral-400 light:text-neutral-500 sm:gap-2.5',
        className,
      )}
      {...props}
    />
  ),
);
BaseBreadcrumbList.displayName = 'BaseBreadcrumbList';

const BaseBreadcrumbItem = forwardRef<HTMLLIElement, ComponentPropsWithoutRef<'li'>>(
  ({ className, ...props }, ref) => (
    <li ref={ref} className={cn('inline-flex items-center gap-1.5', className)} {...props} />
  ),
);
BaseBreadcrumbItem.displayName = 'BaseBreadcrumbItem';

const BaseBreadcrumbLink = forwardRef<
  HTMLAnchorElement,
  ComponentPropsWithoutRef<'a'> & {
    asChild?: boolean;
  }
>(({ asChild, className, ...props }, ref) => {
  const Comp = asChild ? Slot : 'a';

  return (
    <Comp
      ref={ref}
      className={cn('hover:text-neutral-50 light:hover:text-neutral-950', className)}
      {...props}
    />
  );
});
BaseBreadcrumbLink.displayName = 'BaseBreadcrumbLink';

const BaseBreadcrumbPage = forwardRef<HTMLSpanElement, ComponentPropsWithoutRef<'span'>>(
  ({ className, ...props }, ref) => (
    <span
      ref={ref}
      role="link"
      aria-disabled="true"
      aria-current="page"
      className={cn('font-normal text-neutral-50 light:text-neutral-950', className)}
      {...props}
    />
  ),
);
BaseBreadcrumbPage.displayName = 'BaseBreadcrumbPage';

const BaseBreadcrumbSeparator = ({ children, className, ...props }: ComponentProps<'li'>) => (
  <li
    role="presentation"
    aria-hidden="true"
    className={cn('[&>svg]:size-3.5', className)}
    {...props}
  >
    {children ?? <LuChevronRight />}
  </li>
);
BaseBreadcrumbSeparator.displayName = 'BaseBreadcrumbSeparator';

const BaseBreadcrumbEllipsis = ({ className, ...props }: ComponentProps<'span'>) => (
  <span
    role="presentation"
    aria-hidden="true"
    className={cn('flex h-9 w-9 items-center justify-center', className)}
    {...props}
  >
    <LuMoreHorizontal className="h-4 w-4" />
    <span className="sr-only">More</span>
  </span>
);
BaseBreadcrumbEllipsis.displayName = 'BaseBreadcrumbElipssis';

export {
  BaseBreadcrumb,
  BaseBreadcrumbEllipsis,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
};
