import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

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

  it('given the user does not have event management permissions, does not show the manage button', () => {
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
        can: { manageEvents: false },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
  });
});
