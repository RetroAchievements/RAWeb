import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createGameSetRequestData } from '@/test/factories';

import { AchievementSetEmptyState } from './AchievementSetEmptyState';
import { expect } from 'vitest';

describe('Component: AchievementSetEmptyState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given setRequestData is null, renders nothing', () => {
    // ARRANGE
    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: null, // !!
      },
    });

    // ASSERT
    expect(screen.queryByText(/no achievements yet/i)).not.toBeInTheDocument();
  });

  it('displays the correct labels', () => {
    // ARRANGE
    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData(),
      },
    });

    // ASSERT
    expect(screen.getByText('No achievements yet')).toBeVisible();
    expect(
      screen.getByText('Set requests help developers decide what games to work on next.'),
    ).toBeVisible();
  });

  it('displays the request set toggle button', () => {
    // ARRANGE
    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /request/i })).toBeVisible();
  });

  it('displays the total requests count', () => {
    // ARRANGE
    const setRequestData = createGameSetRequestData({ totalRequests: 123 }); // !!

    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() }, // !!
        backingGame: createGame({ id: 456 }),
        setRequestData,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /123/i })).toHaveAttribute(
      'href',
      expect.stringContaining('game.requests.index,'),
    );
  });

  it('given the user is not logged in, the total requests count is not a link', () => {
    // ARRANGE
    const setRequestData = createGameSetRequestData({ totalRequests: 123 }); // !!

    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: null, // !!
        backingGame: createGame({ id: 456 }),
        setRequestData,
      },
    });

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
    expect(screen.getByText(/123 requests/i)).toBeVisible();
    expect(screen.getByText(/from players/i)).toBeVisible();
  });

  it('given the user is not authenticated, does not display remaining requests', () => {
    // ARRANGE
    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: null, // !!
        backingGame: createGame(),
        setRequestData: createGameSetRequestData(),
      },
    });

    // ASSERT
    expect(screen.queryByText(/my remaining requests/i)).not.toBeInTheDocument();
  });

  it('given the user is authenticated, displays remaining requests', () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const setRequestData = createGameSetRequestData({ userRequestsRemaining: 3 }); // !!

    render(<AchievementSetEmptyState />, {
      pageProps: {
        auth: { user },
        backingGame: createGame(),
        can: {},
        setRequestData,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /3/ })).toBeVisible();
  });
});
