import { render, screen } from '@/test';
import { createGame, createRaEvent, createZiggyProps } from '@/test/factories';

import { EventShowMainRoot } from './EventShowMainRoot';

describe('Component: EventShowMainRoot', () => {
  beforeEach(() => {
    const mockIntersectionObserver = vi.fn();
    mockIntersectionObserver.mockReturnValue({
      observe: () => null,
      unobserve: () => null,
      disconnect: () => null,
    });
    window.IntersectionObserver = mockIntersectionObserver;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        imageIngameUrl: 'test.jpg',
        imageTitleUrl: 'test.jpg',
      }),
    });

    const { container } = render(<EventShowMainRoot />, {
      pageProps: {
        event,
        ziggy: createZiggyProps({ device: 'mobile' }),
        can: { manageEvents: false },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no legacy game, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: undefined,
    });

    render(<EventShowMainRoot />, {
      pageProps: {
        event,
        can: { manageEvents: false },
      },
    });

    // ASSERT
    expect(screen.queryByTestId('main')).not.toBeInTheDocument();
  });

  it('given the device is not desktop, renders the header and breadcrumbs', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        title: 'Achievement of the Week 2025',
        imageIngameUrl: 'test.jpg',
        imageTitleUrl: 'test.jpg',
      }),
    });

    render(<EventShowMainRoot />, {
      pageProps: {
        event,
        ziggy: createZiggyProps({ device: 'mobile' }),
        can: { manageEvents: false },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /achievement of the week 2025/i })).toBeVisible();
  });

  it('given the device is desktop, does not render the header inline', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        title: 'Achievement of the Week 2025',
        imageIngameUrl: 'test.jpg',
        imageTitleUrl: 'test.jpg',
      }),
    });

    render(<EventShowMainRoot />, {
      pageProps: {
        event,
        ziggy: createZiggyProps({ device: 'desktop' }),
        can: { manageEvents: false },
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('heading', { name: /achievement of the week 2025/i }),
    ).not.toBeInTheDocument();
  });
});
