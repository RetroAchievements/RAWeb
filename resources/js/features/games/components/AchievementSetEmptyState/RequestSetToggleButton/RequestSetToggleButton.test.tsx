import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGame, createGameSetRequestData, createZiggyProps } from '@/test/factories';

import { RequestSetToggleButton } from './RequestSetToggleButton';

vi.mock('@/common/components/+vendor/BaseToaster', () => ({
  BaseToaster: () => null,
  toastMessage: {
    promise: vi.fn((promise) => promise),
  },
}));

Object.defineProperty(window, 'location', {
  value: { assign: vi.fn() },
  writable: true,
});

describe('Component: RequestSetToggleButton', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
    vi.mocked(window.location.assign).mockClear();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RequestSetToggleButton />, {
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
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: null, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the set is not requested, shows "Request Set" text', () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: false }), // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /request set/i })).toBeVisible();
  });

  it('given the set is requested, shows "Requested" text', () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: true }), // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /requested/i })).toBeVisible();
  });

  it('given the set is not requested, renders the button without a pressed state', () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: false }), // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button')).not.toBePressed();
  });

  it('given the set is requested, renders the button with a pressed state', () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: true }), // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button')).toBePressed();
  });

  it('given the user is not authenticated and clicks the button, redirects to login', async () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: null, // !!
        backingGame: createGame(),
        setRequestData: createGameSetRequestData(),
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(window.location.assign).toHaveBeenCalledWith(route('login'));
  });

  it('given the user clicks to request a set, calls the API route correctly', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });
    const user = createAuthenticatedUser();
    const game = createGame({ id: 123 });

    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user },
        backingGame: game,
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: false }), // !!
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(route('api.game.set-request.store', { game: 123 }));
    });
  });

  it('given the user clicks to remove a request, calls the API route correctly', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: {} });
    const user = createAuthenticatedUser();
    const game = createGame({ id: 456 });

    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user },
        backingGame: game,
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: true }), // !!
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(deleteSpy).toHaveBeenCalledWith(route('api.game.set-request.destroy', { game: 456 }));
    });
  });

  it('given an API call fails, reverts the button state', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('API Error'));
    const user = createAuthenticatedUser();
    const game = createGame();

    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user },
        backingGame: game,
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: false }), // !!
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(screen.queryByTestId('disabled')).not.toBeInTheDocument();

    const button = screen.getByRole('button');
    await waitFor(() => {
      expect(button).not.toBePressed();
    });
    expect(button).toHaveTextContent('Request Set');
  });

  it('given the user has no requests remaining and has not requested this game, shows disabled tooltip', () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        setRequestData: createGameSetRequestData({
          userRequestsRemaining: 0, // !!
          hasUserRequestedSet: false, // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByTestId('disabled')).toBeVisible();
  });

  it('given the user has negative requests remaining and has not requested, shows disabled tooltip', () => {
    // ARRANGE
    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        setRequestData: createGameSetRequestData({
          userRequestsRemaining: -1, // !!
          hasUserRequestedSet: false, // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByTestId('disabled')).toBeVisible();
  });

  it('given the user has no requests remaining but has already requested, allows unrequesting', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: {} });
    const user = createAuthenticatedUser();
    const game = createGame({ id: 789 });

    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user },
        backingGame: game,
        setRequestData: createGameSetRequestData({
          userRequestsRemaining: 0, // !!
          hasUserRequestedSet: true, // !!
        }),
      },
    });

    // ACT
    const button = screen.getByRole('button', { name: /requested/i });
    expect(button).not.toBeDisabled();
    await userEvent.click(button);

    // ASSERT
    await waitFor(() => {
      expect(deleteSpy).toHaveBeenCalledWith(route('api.game.set-request.destroy', { game: 789 }));
    });
  });

  it('given the button is clicked, the button remains disabled for 2 seconds', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });
    const user = createAuthenticatedUser();
    const game = createGame();

    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user },
        backingGame: game,
        setRequestData: createGameSetRequestData({ hasUserRequestedSet: false }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    // ... button should be disabled immediately after clicking ...
    expect(screen.getByRole('button')).toBeDisabled();

    // ... wait for the button to be re-enabled after the debounce period ...
    await waitFor(
      () => {
        expect(screen.getByRole('button')).not.toBeDisabled();
      },
      { timeout: 3000 }, // Wait up to 3 seconds for debounce.
    );
  });

  it('given the user toggles the button multiple times quickly, resets the debounce timer', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} }).mockResolvedValueOnce({ data: {} });
    const user = createAuthenticatedUser();
    const game = createGame();

    render(<RequestSetToggleButton />, {
      pageProps: {
        auth: { user },
        backingGame: game,
        setRequestData: createGameSetRequestData({
          hasUserRequestedSet: false,
          userRequestsRemaining: 5, // !!
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));
    expect(screen.getByRole('button')).toBeDisabled();

    await waitFor(
      () => {
        expect(screen.getByRole('button')).not.toBeDisabled();
      },
      { timeout: 3000 },
    );

    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(screen.getByRole('button')).toBeDisabled();

    await waitFor(
      () => {
        expect(screen.getByRole('button')).not.toBeDisabled();
      },
      { timeout: 3000 },
    );
  });
});
