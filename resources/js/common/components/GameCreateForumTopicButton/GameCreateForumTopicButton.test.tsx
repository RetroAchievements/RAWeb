import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createGame } from '@/test/factories';

import { GameCreateForumTopicButton } from './GameCreateForumTopicButton';

describe('Component: GameCreateForumTopicButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    const { container } = render(<GameCreateForumTopicButton game={game} />, {
      pageProps: { can: {} },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user cannot create game official forum topics, renders nothing', () => {
    // ARRANGE
    const game = createGame({ id: 1, forumTopicId: undefined });

    render(<GameCreateForumTopicButton game={game} />, {
      pageProps: {
        can: {
          createGameForumTopic: false, // !!
        },
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the game already has a forum topic, renders nothing', () => {
    // ARRANGE
    const game = createGame({ id: 1, forumTopicId: 123 });

    render(<GameCreateForumTopicButton game={game} />, {
      pageProps: {
        can: {
          createGameForumTopic: true,
        },
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the game has no forum topic and the user can create forum topics, shows the button', () => {
    // ARRANGE
    const game = createGame({ id: 1, forumTopicId: undefined });

    render(<GameCreateForumTopicButton game={game} />, {
      pageProps: { can: { createGameForumTopic: true } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new forum topic/i })).toBeVisible();
  });

  it('given the user clicks create but cancels the confirmation, does not make an API call', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementation(() => false);
    const postSpy = vi.spyOn(axios, 'post');

    const game = createGame({ id: 1, forumTopicId: undefined });

    render(<GameCreateForumTopicButton game={game} />, {
      pageProps: { can: { createGameForumTopic: true } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /create new forum topic/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user confirms creating a forum topic, makes the API call and redirects on success', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());
    vi.spyOn(window, 'confirm').mockImplementation(() => true);
    vi.spyOn(axios, 'post').mockResolvedValue({ data: { success: true, topicId: 456 } });

    const game = createGame({ id: 1, forumTopicId: undefined });

    render(<GameCreateForumTopicButton game={game} />, {
      pageProps: { can: { createGameForumTopic: true } },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /create new forum topic/i }));

    // ASSERT
    await waitFor(() => {
      expect(visitSpy).toHaveBeenCalledWith(['forum-topic.show', { topic: 456 }]);
    });
  });
});
