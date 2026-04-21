import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAchievementSetClaim,
  createGame,
  createGameAchievementSet,
  createGamePageClaimData,
} from '@/test/factories';

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
          game: createGame({ gameAchievementSets: [] }),
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Create primary claim?' })).toBeVisible();
  });

  it('given the action is create for a new set, shows the primary claim title and confirm button text', async () => {
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Create primary claim?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Create primary claim' })).toBeVisible();
  });

  it('given the action is create for a revision, shows the revision claim title and confirm button text', async () => {
    // ARRANGE
    const achievementSet = createAchievementSet({ achievements: [createAchievement()] });
    const gameAchievementSet = createGameAchievementSet({ achievementSet });

    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [gameAchievementSet] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Create revision claim?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Create revision claim' })).toBeVisible();
  });

  it('given the action is create for a collaboration, shows the generic title and the collaboration claim confirm button text', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({ wouldBeCollaboration: true }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Are you sure?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Create collaboration claim' })).toBeVisible();
  });

  it('given the action is create for a collaboration with a notice, shows the collaboration claim title', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ forumTopicId: undefined }),
        claimData: createGamePageClaimData({ wouldBeCollaboration: true }),
        game: createGame({ forumTopicId: undefined, gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Create collaboration claim?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Create collaboration claim' })).toBeVisible();
  });

  it('given the action is create and the user has 2 or more unresolved tickets, requires acknowledgment before confirming', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({ numUnresolvedTickets: 2 }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(
      screen.getByRole('checkbox', {
        name: 'I understand and want to continue.',
      }),
    ).toBeVisible();
    expect(screen.getByRole('button', { name: 'Create primary claim' })).toBeDisabled();
  });

  it('given the action is create and the user acknowledges the unresolved tickets warning, allows confirming the claim', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { success: true } });
    vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const backingGame = createGame({ id: 123 });

    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame,
        claimData: createGamePageClaimData({ numUnresolvedTickets: 2 }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(
      screen.getByRole('checkbox', {
        name: 'I understand and want to continue.',
      }),
    );
    await userEvent.click(screen.getByRole('button', { name: 'Create primary claim' }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: 123 }));
  });

  it('given the action is create and the dialog closes, resets the unresolved tickets acknowledgment', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({ numUnresolvedTickets: 2 }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(
      screen.getByRole('checkbox', {
        name: 'I understand and want to continue.',
      }),
    );
    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    await waitFor(() => {
      expect(
        screen.queryByRole('heading', { name: 'Create primary claim?' }),
      ).not.toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(
      screen.getByRole('checkbox', {
        name: 'I understand and want to continue.',
      }),
    ).not.toBeChecked();
    expect(screen.getByRole('button', { name: 'Create primary claim' })).toBeDisabled();
  });

  it('given the action is create and the claim would be a collaboration, does not require unresolved tickets acknowledgment', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="create" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numUnresolvedTickets: 2,
          wouldBeCollaboration: true,
        }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(
      screen.queryByRole('checkbox', {
        name: 'I understand and want to continue.',
      }),
    ).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create collaboration claim' })).toBeEnabled();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Are you sure?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Drop claim' })).toBeVisible();
  });

  it('given the action is not create, does not require unresolved tickets acknowledgment', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="drop" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({ numUnresolvedTickets: 2 }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(
      screen.queryByRole('checkbox', {
        name: 'I understand and want to continue.',
      }),
    ).not.toBeInTheDocument();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Extend claim?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Extend claim' })).toBeVisible();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Are you sure?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Complete claim' })).toBeVisible();
  });

  it('given the action is complete and it is a quick completion, shows the complete claim title', async () => {
    // ARRANGE
    render(<ClaimConfirmationDialog action="complete" trigger={<button>Trigger</button>} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ minutesActive: 60 }),
        }),
        game: createGame({ gameAchievementSets: [] }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Complete claim?' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Complete claim' })).toBeVisible();
  });

  it('given the cancel button is clicked, closes the dialog', async () => {
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    // ASSERT
    await waitFor(() => {
      expect(
        screen.queryByRole('heading', { name: 'Create primary claim?' }),
      ).not.toBeInTheDocument();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(screen.getByRole('button', { name: 'Create primary claim' }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: 123 }));

    await waitFor(() => {
      expect(
        screen.queryByRole('heading', { name: 'Create primary claim?' }),
      ).not.toBeInTheDocument();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(screen.getByRole('button', { name: 'Drop claim' }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.delete', { game: 456 }));

    await waitFor(() => {
      expect(screen.queryByRole('heading', { name: 'Drop claim?' })).not.toBeInTheDocument();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(screen.getByRole('button', { name: 'Extend claim' }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });
    expect(postSpy).toHaveBeenCalledWith(route('achievement-set-claim.create', { game: 789 }));

    await waitFor(() => {
      expect(screen.queryByRole('heading', { name: 'Extend claim?' })).not.toBeInTheDocument();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(screen.getByRole('button', { name: 'Complete claim' }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledOnce();
    });

    const [url, formData] = postSpy.mock.calls[0];
    expect(url).toEqual(route('achievement-set-claim.update', { claim: 999 }));
    expect(formData).toBeInstanceOf(FormData);

    await waitFor(() => {
      expect(screen.queryByRole('heading', { name: 'Complete claim?' })).not.toBeInTheDocument();
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
    await userEvent.click(screen.getByRole('button', { name: 'Trigger' }));
    await userEvent.click(screen.getByRole('button', { name: 'Complete claim' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('heading', { name: 'Complete claim?' })).not.toBeInTheDocument();
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
