import { createAuthenticatedUser } from '@/common/models';
import { ClaimStatus } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAchievementSetClaim,
  createGame,
  createGameAchievementSet,
  createGamePageClaimData,
} from '@/test/factories';

import { SidebarClaimButtons } from './SidebarClaimButtons';

describe('Component: SidebarClaimButtons', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are claims in review, does not render anything', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [createAchievementSetClaim({ status: ClaimStatus.InReview })],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the user can create claims and has claims remaining and no existing claim and no achievements exist, shows create new claim button', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          userClaim: null,
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new claim/i })).toBeVisible();
    expect(
      screen.queryByRole('button', { name: /create new revision claim/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user can create claims and has claims remaining and no existing claim and achievements exist, shows a create new revision claim button', () => {
    // ARRANGE
    const achievements = [createAchievement()];
    const achievementSet = createAchievementSet({ achievements });
    const gameAchievementSet = createGameAchievementSet({ achievementSet });

    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          userClaim: null,
        }),
        game: createGame({ gameAchievementSets: [gameAchievementSet] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new revision claim/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /create new claim/i })).not.toBeInTheDocument();
  });

  it('given the user is the sole author and has no claims remaining, still shows the create claim button', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 0,
          isSoleAuthor: true,
          userClaim: null,
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /create new claim/i })).toBeVisible();
  });

  it('given the user has a completable claim, shows the complete claim button', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isCompletable: true }),
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /complete claim/i })).toBeVisible();
  });

  it('given the user has an extendable claim, shows the extend claim button', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isExtendable: true, isDroppable: false }),
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /extend claim/i })).toBeVisible();
  });

  it('given the user has a droppable claim, shows the drop claim button', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isExtendable: false, isDroppable: true }),
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /drop claim/i })).toBeVisible();
  });

  it('given the user has both an extendable and droppable claim, shows both buttons in a grid', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isExtendable: true, isDroppable: true }),
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /extend claim/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /drop claim/i })).toBeVisible();

    const dropButton = screen.getByRole('button', { name: /drop claim/i });
    expect(dropButton.parentElement).toHaveClass('grid-cols-2');
  });

  it('given the game is a subset, shows the subset indicator on buttons', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame({ id: 999 }),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          userClaim: null,
        }),
        game: createGame({ id: 1, gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /subset/i })).toBeVisible();
  });

  it('given multiple conditions are met, shows all applicable buttons', () => {
    // ARRANGE
    render(<SidebarClaimButtons />, {
      pageProps: {
        achievementSetClaims: [],
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          userClaim: createAchievementSetClaim({
            isCompletable: true,
            isExtendable: true,
            isDroppable: true,
          }),
        }),
        game: createGame({ gameAchievementSets: [] }),
        targetAchievementSetId: null,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /complete claim/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /extend claim/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /drop claim/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /create new claim/i })).not.toBeInTheDocument();
  });
});
