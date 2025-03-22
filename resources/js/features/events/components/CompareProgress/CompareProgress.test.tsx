import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createFollowedPlayerCompletion, createGame, createUser } from '@/test/factories';

import { CompareProgress } from './CompareProgress';

// Suppress JSDOM errors that are not relevant.
console.error = vi.fn();

describe('Component: CompareProgress', () => {
  const mockLocationAssign = vi.fn();

  beforeEach(() => {
    vi.clearAllMocks();

    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });
  });

  it('renders without crashing', () => {
    // ARRANGE
    const followedPlayerCompletions = [createFollowedPlayerCompletion()];
    const game = createGame();

    const { container } = render(
      <CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, renders nothing', () => {
    // ARRANGE
    const followedPlayerCompletions = [createFollowedPlayerCompletion()];
    const game = createGame();

    render(<CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />, {
      pageProps: {
        auth: null,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('compare-progress')).not.toBeInTheDocument();
  });

  it('given no followed users, shows a message', () => {
    // ARRANGE
    const followedPlayerCompletions: App.Platform.Data.FollowedPlayerCompletion[] = [];
    const game = createGame();

    render(<CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText(/no one you follow has unlocked any achievements/i)).toBeVisible();
  });

  it('given there are followed users, renders the PopulatedPlayerCompletions component', () => {
    // ARRANGE
    const followedPlayerCompletions = [
      createFollowedPlayerCompletion({
        user: createUser({ displayName: 'FollowedUser1' }),
      }),
    ];
    const game = createGame();

    render(<CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText('FollowedUser1')).toBeVisible();
    expect(
      screen.queryByText(/no one you follow has unlocked any achievements/i),
    ).not.toBeInTheDocument();
  });

  it('given the user selects a player to compare with, navigates to the compare page', async () => {
    // ARRANGE
    const followedPlayerCompletions = [createFollowedPlayerCompletion()];
    const game = createGame({ id: 123 });
    const searchResult = createUser({ displayName: 'CompareUser' });

    // Use the same pattern as in CreateMessageThreadForm test
    vi.spyOn(axios, 'get').mockImplementation((url) => {
      if (url.includes('api.search.index')) {
        return Promise.resolve({ data: { users: [searchResult] } });
      }

      return Promise.reject(new Error('Not mocked'));
    });

    render(<CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.type(screen.getByPlaceholderText(/type a username/i), 'CompareUser');
    await waitFor(() => {
      expect(screen.getByText('CompareUser')).toBeVisible();
    });
    await userEvent.click(screen.getByText('CompareUser'));

    // ASSERT
    expect(mockLocationAssign).toHaveBeenCalledWith(
      route('game.compare-unlocks', { user: 'CompareUser', game: 123 }),
    );
  });

  it('given the component renders, displays the correct heading', () => {
    // ARRANGE
    const followedPlayerCompletions = [createFollowedPlayerCompletion()];
    const game = createGame();

    render(<CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText(/compare progress/i)).toBeVisible();
  });

  it('given the component renders, displays the select with the correct placeholder', () => {
    // ARRANGE
    const followedPlayerCompletions = [createFollowedPlayerCompletion()];
    const game = createGame();

    render(<CompareProgress followedPlayerCompletions={followedPlayerCompletions} game={game} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText(/compare with any user/i)).toBeVisible();
  });
});
