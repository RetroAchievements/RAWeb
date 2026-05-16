/* eslint-disable no-restricted-imports -- this component wraps Inertia's <Link /> */

import type { Visit, VisitCallbacks } from '@inertiajs/core';
import type { InertiaLinkProps as OriginalInertiaLinkProps } from '@inertiajs/react';
import { Link, router } from '@inertiajs/react';
import { type FC, useCallback, useEffect, useRef } from 'react';
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

export const InertiaLink: FC<InertiaLinkProps> = ({
  href,
  data,
  except,
  headers,
  only,
  preserveScroll,
  preserveState,
  replace,
  method = 'get',
  prefetch = 'never',
  ...rest
}) => {
  const { ziggy } = usePageProps();
  const isMobile = ziggy?.device === 'mobile';

  const hoverTimeoutRef = useRef<number>(undefined);
  const hasMobilePrefetchedRef = useRef(false);

  const safeHref = typeof href === 'string' ? href : String(href || '');

  /**
   * Use an intersection observer for mobile prefetching.
   * For perf, only observe if we want mobile intersection prefetching.
   */
  const { ref, inView } = useInView({
    triggerOnce: true,
    skip: !isMobile || prefetch !== 'desktop-hover-and-mobile-intersect',
  });

  // Inertia uses navigation options to generate cache keys for prefetched requests, so the
  // options we send to prefetch must match those the Link uses during actual navigation.
  // Otherwise, the cache keys diverge and the prefetched payload is discarded.
  const buildPrefetchOptions = useCallback((): Partial<Visit & VisitCallbacks> => {
    const options: Partial<Visit & VisitCallbacks> = { method };

    // Skip undefined values to avoid tripping Inertia's comparison logic.
    if (data !== undefined) options.data = data;
    if (except !== undefined) options.except = except;
    if (headers !== undefined) options.headers = headers;
    if (only !== undefined) options.only = only;
    if (preserveScroll !== undefined) options.preserveScroll = preserveScroll;
    if (preserveState !== undefined) options.preserveState = preserveState;
    if (replace !== undefined) options.replace = replace;

    return options;
  }, [data, except, headers, method, only, preserveScroll, preserveState, replace]);

  const handlePrefetch = useCallback(() => {
    router.prefetch(safeHref, buildPrefetchOptions(), { cacheFor: '30s' });
  }, [buildPrefetchOptions, safeHref]);

  useEffect(() => {
    hasMobilePrefetchedRef.current = false;
  }, [safeHref]);

  /**
   * Handle mobile prefetch via the intersection observer.
   */
  useEffect(() => {
    if (!isMobile || !inView || prefetch !== 'desktop-hover-and-mobile-intersect') {
      return;
    }

    if (hasMobilePrefetchedRef.current) {
      return;
    }

    hasMobilePrefetchedRef.current = true;
    handlePrefetch();
  }, [handlePrefetch, inView, isMobile, prefetch]);

  useEffect(() => {
    return () => {
      if (hoverTimeoutRef.current) {
        clearTimeout(hoverTimeoutRef.current);
      }
    };
  }, []);

  return (
    <Link
      ref={ref}
      href={safeHref}
      data={data}
      except={except}
      headers={headers}
      method={method}
      only={only}
      // We'll manage prefetching explicitly.
      prefetch={false}
      preserveScroll={preserveScroll}
      preserveState={preserveState}
      replace={replace}
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
      data-testid="link"
      {...rest}
    />
  );
};
