import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { EventSidebarFullWidthButtons } from './EventSidebarFullWidthButtons';

describe('Component: EventSidebarFullWidthButtons', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: createGame({ id: 1, title: 'Sonic the Hedgehog' }),
    });

    const { container } = render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: { can: {} },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has an official forum topic, renders the official forum topic link button', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: createGame({ id: 1, title: 'Sonic the Hedgehog', forumTopicId: 9 }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: { can: {} },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /forum topic/i })).toBeVisible();
  });

  it('given the user has permission to manage events, renders the manage event link button', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      id: 123,
      legacyGame: createGame({ id: 1, title: 'Sonic the Hedgehog' }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: {
        can: {
          manageEvents: true, // !!
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
  });

  it('given the user does not have permission to manage events, does not render the manage event link button', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: { id: 1, title: 'Test Game' } as App.Platform.Data.Game,
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: { can: { manageEvents: false } },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
  });
});
