import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createRaEvent, createZiggyProps } from '@/test/factories';

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
    expect(screen.getByText(/essential resources/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /forum topic/i })).toBeVisible();
  });

  it('given the event does not have an official forum topic, does not render an essential resources section', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: createGame({ id: 1, title: 'Sonic the Hedgehog', forumTopicId: undefined }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: { auth: { user: createAuthenticatedUser() }, can: {} },
    });

    // ASSERT
    expect(screen.queryByText(/essential resources/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /forum topic/i })).not.toBeInTheDocument();
  });

  it('given the user can manage events and is on mobile, shows the event details link', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      id: 123,
      legacyGame: createGame({ id: 1, title: 'Sonic the Hedgehog' }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: {
        ziggy: createZiggyProps({ device: 'mobile' }),
        can: { manageEvents: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/management/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /event details/i })).toBeVisible();
  });

  it('given the user can manage events and is on desktop, does not show the event details link', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      id: 123,
      legacyGame: createGame({ id: 1, title: 'Sonic the Hedgehog' }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: {
        ziggy: createZiggyProps({ device: 'desktop' }),
        can: { manageEvents: true },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /event details/i })).not.toBeInTheDocument();
  });

  it('given the user can manage events and the event does not have a forum topic, shows a button to create the forum topic', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: createGame({ id: 1, title: 'Test Game', forumTopicId: undefined }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        ziggy: createZiggyProps({ device: 'mobile' }),
        can: { manageEvents: true, createGameForumTopic: true },
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new forum topic/i })).toBeVisible();
  });

  it('given the user can manage events and the event already has a forum topic, does not show the create forum topic button', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: createGame({ id: 1, title: 'Test Game', forumTopicId: 9 }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        ziggy: createZiggyProps({ device: 'mobile' }),
        can: { manageEvents: true, createGameForumTopic: true },
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /create new forum topic/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user is not logged in and the event has no forum topic, displays nothing', () => {
    // ARRANGE
    const mockEvent = createRaEvent({
      legacyGame: createGame({ id: 1, title: 'Test Game', forumTopicId: undefined }),
    });

    render(<EventSidebarFullWidthButtons event={mockEvent} />);

    // ASSERT
    expect(screen.queryByText(/essential resources/i)).not.toBeInTheDocument();
  });
});
