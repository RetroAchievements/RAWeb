import { createAuthenticatedUser } from '@/common/models';
import { renderHook } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createAchievementSetClaim,
  createGame,
  createGameAchievementSet,
  createGamePageClaimData,
  createUser,
} from '@/test/factories';

import { useClaimDialogState } from './useClaimDialogState';

describe('Hook: useClaimDialogState', () => {
  it('given the action is create with no applicable notices, returns a primary claim without notices', () => {
    // ARRANGE
    const backingGame = createGame({ id: 10, forumTopicId: 123 });

    const { result } = renderHook(() => useClaimDialogState('create'), {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        backingGame,
        claimData: createGamePageClaimData(),
        game: createGame({ id: 10, forumTopicId: 123, gameAchievementSets: [] }),
      },
    });

    // ASSERT
    expect(result.current.createClaimDialogVariant).toEqual('newSet');
    expect(result.current.hasDialogNotice).toEqual(false);
    expect(result.current.requiresTicketAcknowledgment).toEqual(false);
  });

  it("given the action is create for another author's existing set work, returns the revision plan notice state", () => {
    // ARRANGE
    const achievement = createAchievement({
      developer: createUser({ displayName: 'AnotherDev' }),
    });
    const achievementSet = createAchievementSet({ achievements: [achievement] });
    const gameAchievementSet = createGameAchievementSet({ achievementSet });

    const { result } = renderHook(() => useClaimDialogState('create'), {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        backingGame: createGame({ id: 10, forumTopicId: 123 }),
        claimData: createGamePageClaimData(),
        game: createGame({ id: 10, forumTopicId: 123, gameAchievementSets: [gameAchievementSet] }),
      },
    });

    // ASSERT
    expect(result.current.createClaimDialogVariant).toEqual('revision');
    expect(result.current.hasRevisionPlanNotice).toEqual(true);
    expect(result.current.hasDialogNotice).toEqual(true);
  });

  it('given the action is create for a collaboration claim, does not require ticket acknowledgment', () => {
    // ARRANGE
    const { result } = renderHook(() => useClaimDialogState('create'), {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame({ forumTopicId: 123 }),
        claimData: createGamePageClaimData({
          numUnresolvedTickets: 3,
          wouldBeCollaboration: true,
        }),
        game: createGame({ forumTopicId: 123, gameAchievementSets: [] }),
      },
    });

    // ASSERT
    expect(result.current.createClaimDialogVariant).toEqual('collaboration');
    expect(result.current.requiresTicketAcknowledgment).toEqual(false);
  });

  it('given the action is complete and the claim is less than a day old, returns the quick completion warning state', () => {
    // ARRANGE
    const { result } = renderHook(() => useClaimDialogState('complete'), {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        backingGame: createGame(),
        claimData: createGamePageClaimData({
          userClaim: createAchievementSetClaim({ minutesActive: 60 }),
        }),
      },
    });

    // ASSERT
    expect(result.current.hasQuickCompletionWarning).toEqual(true);
    expect(result.current.hasDialogNotice).toEqual(true);
    expect(result.current.quickCompletionMinutesActive).toEqual(60);
  });

  it('given claim data is missing, falls back to safe defaults', () => {
    // ARRANGE
    const backingGame = createGame({ id: 10, forumTopicId: 123 });

    const { result } = renderHook(() => useClaimDialogState('create'), {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        backingGame,
        claimData: undefined,
        game: createGame({ id: 10, forumTopicId: 123, gameAchievementSets: [] }),
      },
    });

    // ASSERT
    expect(result.current.createClaimDialogVariant).toEqual('newSet');
    expect(result.current.unresolvedTicketCount).toEqual(0);
    expect(result.current.requiresTicketAcknowledgment).toEqual(false);
  });
});
