import type { FC, ReactNode } from 'react';

import { InertiaLink } from '@/common/components/InertiaLink';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface StatBoxProps {
  t_label: TranslatedString;
  href: string;
  children: ReactNode;

  anchorClassName?: string;

  /**
   * If the destination page is also a React page, we should client-side
   * route to improve the performance of loading that page.
   */
  asClientSideRoute?: boolean;
}

export const StatBox: FC<StatBoxProps> = ({
  t_label,
  href,
  children,
  anchorClassName,
  asClientSideRoute = false,
}) => {
  const Wrapper = asClientSideRoute ? InertiaLink : 'a';
  const labelId = `${t_label.toLowerCase().replace(/\s+/g, '-')}-label`;

  return (
    <Wrapper
      href={href}
      className={cn(
        'group flex h-full flex-col rounded border bg-embed px-2 py-2.5',
        'border-neutral-700/80 hover:border-neutral-50',
        'light:border-neutral-400 light:hover:border-neutral-900 light:hover:bg-neutral-100',
        anchorClassName,
      )}
    >
      <span
        id={labelId}
        className={cn(
          'text-xs leading-4 lg:text-2xs',
          'text-neutral-400/90 group-hover:text-neutral-50 light:text-neutral-950 light:group-hover:text-neutral-950',
        )}
      >
        {t_label}
      </span>

      <p
        aria-labelledby={labelId}
        className={cn(
          '!text-[20px] leading-7 text-neutral-300',
          'group-hover:text-neutral-50 light:text-neutral-950 light:group-hover:text-neutral-950',
        )}
      >
        {children}
      </p>
    </Wrapper>
  );
};
