/* eslint-disable no-restricted-imports -- this component wraps Inertia's <Link /> */

import type { InertiaLinkProps as OriginalInertiaLinkProps } from '@inertiajs/react';
import { Link, router } from '@inertiajs/react';
import { type FC, useRef } from 'react';
import { useInView } from 'react-intersection-observer';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { LinkPrefetchBehavior } from '@/common/models';

type InertiaLinkProps = Omit<OriginalInertiaLinkProps, 'prefetch'> & {
  /**
   * Controls prefetch behavior:
   * - never: Do not prefetch this link under any circumstance.
   * - desktop-hover-only: Prefetch when the user hovers over the link on desktop.
   * - desktop-hover-and-mobile-intersect: Prefetch on hover for desktop, and on visible for mobile.
   */
  prefetch?: LinkPrefetchBehavior;
};

export const InertiaLink: FC<InertiaLinkProps> = ({
  href,
  prefetch = 'desktop-hover-only',
  ...rest
}) => {
  const { ziggy } = usePageProps();
  const isMobile = ziggy?.device === 'mobile';

  const hoverTimeoutRef = useRef<number>();

  /**
   * Use an intersection observer for mobile prefetching.
   * For perf, only observe if we want mobile intersection prefetching.
   */
  const { ref, inView } = useInView({
    triggerOnce: true,
    skip: !isMobile || prefetch !== 'desktop-hover-and-mobile-intersect',
  });

  const handlePrefetch = () => {
    router.prefetch(href, { method: 'get' }, { cacheFor: '30s' });
  };

  /**
   * Handle mobile prefetch via the intersection observer.
   */
  if (isMobile && inView && prefetch === 'desktop-hover-and-mobile-intersect') {
    handlePrefetch();
  }

  return (
    <Link
      ref={ref}
      href={href}
      // We'll manage prefetching explicitly.
      prefetch={false}
      onMouseEnter={() => {
        if (!isMobile && prefetch !== 'never') {
          hoverTimeoutRef.current = window.setTimeout(handlePrefetch, 75);
        }
      }}
      onMouseLeave={() => {
        if (hoverTimeoutRef.current) {
          clearTimeout(hoverTimeoutRef.current);
        }
      }}
      {...rest}
    />
  );
};
