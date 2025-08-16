import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createAchievementSetClaim, createGame, createGamePageClaimData } from '@/test/factories';

import { ClaimConfirmationDialog } from './ClaimConfirmationDialog';

describe('Component: ClaimConfirmationDialog', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          claimData: createGamePageClaimData(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the trigger is clicked, opens the dialog', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));

    // ASSERT
    expect(screen.getByText(/are you sure\?/i)).toBeVisible();
  });

  it('given the action is create, shows the correct confirm button text', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /yes, create the claim/i })).toBeVisible();
  });

  it('given the action is drop, shows the correct confirm button text', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="drop" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /yes, drop the claim/i })).toBeVisible();
  });

  it('given the action is extend, shows the correct confirm button text', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="extend" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /yes, extend the claim/i })).toBeVisible();
  });

  it('given the action is complete, shows the correct confirm button text', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="complete" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /yes, complete the claim/i })).toBeVisible();
  });

  it('given the nevermind button is clicked, closes the dialog', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));
    await userEvent.click(screen.getByRole('button', { name: /nevermind/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the action is create and the confirm button is clicked, makes the correct API call and closes the dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const backingGame = createGame({ id: 123 });

    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, create the claim/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: 123 }));

    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the action is drop and the confirm button is clicked, makes the correct API call and closes the dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const backingGame = createGame({ id: 456 });

    render(<ClaimConfirmationDialog action="drop" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, drop the claim/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.delete', { game: 456 }));

    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the action is extend and the confirm button is clicked, makes the correct API call and closes the dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const backingGame = createGame({ id: 789 });

    render(<ClaimConfirmationDialog action="extend" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, extend the claim/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: 789 }));

    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the action is complete and the user has a claim and the confirm button is clicked, makes the correct API call and closes the dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const userClaim = createAchievementSetClaim({ id: 999 });

    render(<ClaimConfirmationDialog action="complete" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({ userClaim }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, complete the claim/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });

    const [url, formData] = postSpy.mock.calls[0];
    expect(url).toEqual(route('achievement-set-claim.update', { claim: 999 }));
    expect(formData).toBeInstanceOf(FormData);

    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
  });

  it('given the action is complete and the user has no claim, does not make an API call', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });

    render(<ClaimConfirmationDialog action="complete" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({ userClaim: null }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /trigger/i }));
    await userEvent.click(screen.getByRole('button', { name: /yes, complete the claim/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByText(/are you sure\?/i)).not.toBeInTheDocument();
    });
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the action is something unknown, does not crash', () => {
    // ARRANGE
    const { container } = render(
      <ClaimConfirmationDialog action={'asdfasdfasdf' as any} trigger={<button>Trigger</button>} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          backingGame: createGame(),
          claimData: createGamePageClaimData({ userClaim: null }),
          game: createGame({ gameAchievementSets: [] }),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });
});
