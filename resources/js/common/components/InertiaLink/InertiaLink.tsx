/* eslint-disable no-restricted-imports -- this component wraps Inertia's <Link /> */

import type { Visit, VisitCallbacks } from '@inertiajs/core';
import type { InertiaLinkProps as OriginalInertiaLinkProps } from '@inertiajs/react';
import { Link, router } from '@inertiajs/react';
import { type FC, useRef } from 'react';
import { useInView } from 'react-intersection-observer';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { LinkPrefetchBehavior } from '@/common/models';

export type InertiaLinkProps = Omit<OriginalInertiaLinkProps, 'prefetch'> & {
  /**
   * Controls prefetch behavior:
   * - never: Default. Do not prefetch this link under any circumstance.
   * - desktop-hover-only: Prefetch when the user hovers over the link on desktop.
   * - desktop-hover-and-mobile-intersect: Prefetch on hover for desktop, and on visible for mobile.
   */
  prefetch?: LinkPrefetchBehavior;
};

export const InertiaLink: FC<InertiaLinkProps> = ({ href, prefetch = 'never', ...rest }) => {
  const { ziggy } = usePageProps();
  const isMobile = ziggy?.device === 'mobile';

  const hoverTimeoutRef = useRef<number>(undefined);

  const safeHref = typeof href === 'string' ? href : String(href || '');

  /**
   * Use an intersection observer for mobile prefetching.
   * For perf, only observe if we want mobile intersection prefetching.
   */
  const { ref, inView } = useInView({
    triggerOnce: true,
    skip: !isMobile || prefetch !== 'desktop-hover-and-mobile-intersect',
  });

  const handlePrefetch = () => {
    // Inertia uses navigation options to generate cache keys for prefetched requests.
    // If we don't pass the same options during prefetch that the Link will use during
    // actual navigation, the cache keys won't match and the prefetched data won't be used.
    // This defeats the purpose of prefetching. To fix this, we extract all navigation
    // options from the Link props and pass them to the prefetch call.
    const {
      data,
      preserveScroll,
      preserveState,
      only,
      except,
      headers,
      replace,
      method = 'get',
    } = rest;
    const options: Partial<Visit & VisitCallbacks> = { method };

    // Only include defined navigation options to avoid passing undefined values
    // which could cause issues with Inertia's comparison logic.
    if (data !== undefined) options.data = data;
    if (except !== undefined) options.except = except;
    if (headers !== undefined) options.headers = headers;
    if (only !== undefined) options.only = only;
    if (preserveScroll !== undefined) options.preserveScroll = preserveScroll;
    if (preserveState !== undefined) options.preserveState = preserveState;
    if (replace !== undefined) options.replace = replace;

    router.prefetch(safeHref, options, { cacheFor: '30s' });
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
      href={safeHref}
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
