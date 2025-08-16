import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAchievementSetClaim,
  createGame,
  createGameAchievementSet,
  createGamePageClaimData,
  createUser,
} from '@/test/factories';

import { ClaimDialogDescriptions } from './ClaimDialogDescriptions';

describe('Component: ClaimDialogDescriptions', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the action is create and the claim would be a collaboration, shows collaboration claim text', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame({ title: 'Backing Game' }),
        claimData: createGamePageClaimData({ wouldBeCollaboration: true }),
      },
    });

    // ASSERT
    expect(screen.getByText(/this will create a new collaboration claim/i)).toBeVisible();
    expect(screen.getByText('Backing Game')).toBeVisible();
  });

  it('given the action is create and the claim would not be a collaboration, shows primary claim text', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame({ title: 'Backing Game' }),
        claimData: createGamePageClaimData({ wouldBeCollaboration: false }),
      },
    });

    // ASSERT
    expect(screen.getByText(/this will create a new primary claim/i)).toBeVisible();
    expect(screen.getByText('Backing Game')).toBeVisible();
  });

  it('given the action is create and the claim would be a revision and the user is not the sole author, shows the revision plan warning', () => {
    // ARRANGE
    const mockUser = createAuthenticatedUser({ displayName: 'TestUser' });
    const otherUser = createUser({ displayName: 'OtherUser' });
    const achievements = [
      createAchievement({ developer: otherUser }),
      createAchievement({ developer: mockUser }),
    ];

    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: mockUser },
        game: createGame({
          gameAchievementSets: [
            createGameAchievementSet({
              achievementSet: createAchievementSet({ achievements }),
            }),
          ],
        }),
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByText(/has been posted and approved/i)).toBeVisible();
  });

  it('given the action is create and the user is the sole author of all achievements, does not show the revision plan warning', () => {
    // ARRANGE
    const mockUser = createAuthenticatedUser({ displayName: 'TestUser' });
    const achievements = [
      createAchievement({ developer: mockUser }),
      createAchievement({ developer: mockUser }),
    ];

    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: mockUser },
        game: createGame({
          gameAchievementSets: [
            createGameAchievementSet({
              achievementSet: createAchievementSet({ achievements }),
            }),
          ],
        }),
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.queryByText(/has been posted and approved/i)).not.toBeInTheDocument();
  });

  it('given the action is create and the claim would be a subset and not a revision, shows the subset approval warning', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ id: 1, gameAchievementSets: [] }),
        backingGame: createGame({ id: 999 }), // Different ID = subset.
        claimData: createGamePageClaimData(),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByText(/please only create this claim if the subset/i)).toBeVisible();
  });

  it('given the action is create and there are unresolved tickets, shows the unresolved tickets warning', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData({ numUnresolvedTickets: 5 }),
      },
    });

    // ASSERT
    expect(screen.getByText(/please ensure any open tickets have been addressed/i)).toBeVisible();
  });

  it('given the action is create and the game has no forum topic, shows the forum topic notice', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="create" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ forumTopicId: undefined, gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(
      screen.getByText(/an official forum topic for the game will also be created/i),
    ).toBeVisible();
  });

  it('given the action is drop, shows drop claim text with the game title', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="drop" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame({ title: 'Backing Game' }),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(screen.getByText(/this will drop your current claim/i)).toBeVisible();
    expect(screen.getByText('Backing Game')).toBeVisible();
  });

  it('given the action is extend, shows extend claim text and progress report warning', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="extend" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(screen.getByText(/this will extend the claim for another three months/i)).toBeVisible();
    expect(screen.getByText(/post a progress report/i)).toBeVisible();
  });

  it('given the action is complete and it is a quick completion, shows complete claim text and the quick completion warning', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="complete" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ minutesActive: 120 }), // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/this will inform all set requestors/i)).toBeVisible();
    expect(
      screen.getByText(/please ensure you have approval to complete this claim/i),
    ).toBeVisible();
  });

  it('given the action is complete and it is not a quick completion, does not show the quick completion warning', () => {
    // ARRANGE
    render(<ClaimDialogDescriptions action="complete" />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ minutesActive: 99999 }), // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/this will inform all set requestors/i)).toBeVisible();
    expect(
      screen.queryByText(/please ensure you have approval to complete this claim/i),
    ).not.toBeInTheDocument();
  });

  it('given an unknown action type, does not crash', () => {
    // ARRANGE
    const { container } = render(<ClaimDialogDescriptions action={'unknown' as any} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        game: createGame({ gameAchievementSets: [] }),
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });
});
