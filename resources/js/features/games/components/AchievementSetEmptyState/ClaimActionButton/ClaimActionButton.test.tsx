import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createAchievementSetClaim, createGame, createGamePageClaimData } from '@/test/factories';

import { ClaimActionButton } from './ClaimActionButton';

describe('Component: ClaimActionButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, does not render anything', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: null,
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the user does not have the developer role, does not render anything', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: [] }) }, // !!
        backingGame: createGame(),
        claimData: createGamePageClaimData(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the user has an extendable claim, shows the extend claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isExtendable: true }), // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /extend claim/i })).toBeVisible();
  });

  it('given the user has a claim that is not droppable, shows a fake disabled drop claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isExtendable: false, isDroppable: false }), // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/drop claim/i)).toBeVisible();
    expect(screen.queryByTestId('claim-button')).not.toBeInTheDocument();
  });

  it('given the user has a droppable claim, shows the real drop claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ isExtendable: false, isDroppable: true }), // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /drop claim/i })).toBeVisible();
  });

  it('given the user has no claims remaining and is not the sole author, shows a fake disabled claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 0,
          isSoleAuthor: false,
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/claim/i)).toBeVisible();
    expect(screen.queryByTestId('claim-button')).not.toBeInTheDocument();
  });

  it('given the user has 2 or more unresolved tickets, shows a fake disabled claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          numUnresolvedTickets: 2, // !!
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/claim/i)).toBeVisible();
    expect(screen.queryByTestId('claim-button')).not.toBeInTheDocument();
  });

  it('given the user is a junior developer and the game has no forum topic, shows a fake disabled claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            roles: ['developer-junior'], // !!
          }),
        },
        backingGame: createGame({ forumTopicId: undefined }), // !!
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          numUnresolvedTickets: 0,
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/claim/i)).toBeVisible();
    expect(screen.queryByTestId('claim-button')).not.toBeInTheDocument();
  });

  it('given the user can make a new claim and all conditions are met, shows the real claim button', () => {
    // ARRANGE
    render(<ClaimActionButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        backingGame: createGame({ forumTopicId: 12345 }),
        claimData: createGamePageClaimData({
          numClaimsRemaining: 1,
          numUnresolvedTickets: 0,
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /claim/i })).toBeVisible();
  });
});
