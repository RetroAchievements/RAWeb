import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import { mockAllIsIntersecting } from 'react-intersection-observer/test-utils';

import { render, screen } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { InertiaLink } from './InertiaLink';

describe('Component: InertiaLink', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<InertiaLink href="/">Link Text</InertiaLink>, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is on desktop and hovers over a link, by default prefetches the route', async () => {
    // ARRANGE
    const prefetchSpy = vi.spyOn(router, 'prefetch');

    render(<InertiaLink href="/test">Link Text</InertiaLink>, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /link text/i }));

    // ASSERT
    expect(prefetchSpy).toHaveBeenCalledWith('/test', { method: 'get' }, { cacheFor: '30s' });
  });

  it('given the user is on mobile and hovers (taps) the link with default prefetch behavior, does not prefetch the route', async () => {
    // ARRANGE
    const prefetchSpy = vi.spyOn(router, 'prefetch');

    render(<InertiaLink href="/test">Link Text</InertiaLink>, {
      pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /link text/i }));

    // ASSERT
    expect(prefetchSpy).not.toHaveBeenCalled();
  });

  it('given the prefetch prop is set to "never", does not prefetch on hover on desktop', async () => {
    // ARRANGE
    const prefetchSpy = vi.spyOn(router, 'prefetch');

    render(
      <InertiaLink href="/test" prefetch="never">
        Link Text
      </InertiaLink>,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
      },
    );

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: /link text/i }));

    // ASSERT
    expect(prefetchSpy).not.toHaveBeenCalled();
  });

  it('given the link is NOT visible on mobile with the "desktop-hover-and-mobile-intersect" strategy enabled, does not prefetch', async () => {
    // ARRANGE
    const prefetchSpy = vi.spyOn(router, 'prefetch');

    render(
      <InertiaLink href="/test" prefetch="desktop-hover-and-mobile-intersect">
        Link Text
      </InertiaLink>,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
      },
    );

    // ACT
    // mockAllIsIntersecting(true); !! intentionally disabled ... the user doesn't see the link yet.

    // ASSERT
    expect(prefetchSpy).not.toHaveBeenCalled();
  });

  it('given the link is visible on mobile with the "desktop-hover-and-mobile-intersect" strategy enabled, prefetches when the link is visible', async () => {
    // ARRANGE
    const prefetchSpy = vi.spyOn(router, 'prefetch');

    render(
      <InertiaLink href="/test" prefetch="desktop-hover-and-mobile-intersect">
        Link Text
      </InertiaLink>,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
      },
    );

    // ACT
    mockAllIsIntersecting(true);

    // ASSERT
    expect(prefetchSpy).toHaveBeenCalled();
  });
});
