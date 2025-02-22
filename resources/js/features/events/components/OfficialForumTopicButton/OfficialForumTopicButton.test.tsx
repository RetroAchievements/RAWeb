import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { OfficialForumTopicButton } from './OfficialForumTopicButton';

// Suppress expected error logs.
console.error = vi.fn();

describe('Component: OfficialForumTopicButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame({ id: 1 }) });
    const { container } = render(<OfficialForumTopicButton event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no associated legacy game, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: undefined });
    render(<OfficialForumTopicButton event={event} />);

    // ASSERT
    expect(screen.queryByText(/topic/i)).not.toBeInTheDocument();
  });

  it('given the game has no forum topic and the user can create forum topics, shows the create button', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame({ id: 1, forumTopicId: undefined }) });
    render(<OfficialForumTopicButton event={event} />, {
      pageProps: { can: { createGameForumTopic: true } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new forum topic/i })).toBeVisible();
  });

  it('given the game has no forum topic and the user cannot create forum topics, does not show a create button', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame({ id: 1, forumTopicId: undefined }) });
    render(<OfficialForumTopicButton event={event} />, {
      pageProps: { can: { createGameForumTopic: false } },
    });

    expect(screen.queryByRole('button')).not.toBeInTheDocument();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given the game has a forum topic, shows a link to it', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame({ id: 1, forumTopicId: 123 }) });
    render(<OfficialForumTopicButton event={event} />);

    // ASSERT
    const link = screen.getByRole('link', { name: /official forum topic/i });
    expect(link).toBeVisible();
    expect(link).toHaveAttribute('href', '/viewtopic.php?t=123');
  });

  it('given the user clicks create but cancels the confirmation, does not make an API call', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementation(() => false);
    const postSpy = vi.spyOn(axios, 'post');

    const event = createRaEvent({ legacyGame: createGame({ id: 1, forumTopicId: undefined }) });
    render(<OfficialForumTopicButton event={event} />, {
      pageProps: { can: { createGameForumTopic: true } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /create new forum topic/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user confirms creating a forum topic, makes the API call and redirects on success', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementation(() => true);
    const mockLocationAssign = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });

    vi.spyOn(axios, 'post').mockResolvedValue({ data: { success: true, topicId: 456 } });

    const event = createRaEvent({ legacyGame: createGame({ id: 1, forumTopicId: undefined }) });
    render(<OfficialForumTopicButton event={event} />, {
      pageProps: { can: { createGameForumTopic: true } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /create new forum topic/i }));

    // ASSERT
    await waitFor(() => {
      expect(mockLocationAssign).toHaveBeenCalledWith('/viewtopic.php?t=456');
    });
  });
});
