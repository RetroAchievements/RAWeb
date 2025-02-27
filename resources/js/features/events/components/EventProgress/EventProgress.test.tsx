import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createAchievement,
  createEventAward,
  createPlayerGame,
  createRaEvent,
} from '@/test/factories';

import { EventProgress } from './EventProgress';

describe('Component: EventProgress', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<EventProgress event={event} playerGame={null} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is unauthenticated, shows a sign in call to action', () => {
    // ARRANGE
    const event = createRaEvent();

    render(<EventProgress event={event} playerGame={null} />, {
      pageProps: { auth: null },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /sign in/i })).toBeVisible();
  });

  it('given the authenticated user has not unlocked any achievements, shows the empty state message', () => {
    // ARRANGE
    const event = createRaEvent();

    render(<EventProgress event={event} playerGame={null} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.getByText(/haven't unlocked any achievements/i)).toBeVisible();
    expect(screen.queryByText(/of.*achievements/i)).not.toBeInTheDocument();
  });

  it('given the user has unlocked achievements, shows the progress text', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 5 }), isObfuscated: false },
        { achievement: createAchievement({ points: 5 }), isObfuscated: false },
      ],
    });
    const playerGame = createPlayerGame({ achievementsUnlocked: 1, pointsHardcore: 5 });

    render(<EventProgress event={event} playerGame={playerGame} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.getAllByText(/1/)[0]).toBeVisible();
    expect(screen.getByText(/of 2 achievements/i)).toBeVisible();
    expect(screen.getAllByText(/5/)[0]).toBeVisible();
    expect(screen.getByText(/of 10 points/i)).toBeVisible();
  });

  it('correctly falls back to 0 points for achievements with undefined points', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 5 }), isObfuscated: false },
        { achievement: createAchievement({ points: undefined }), isObfuscated: false },
      ],
    });
    const playerGame = createPlayerGame({ achievementsUnlocked: 1, pointsHardcore: 5 });

    render(<EventProgress event={event} playerGame={playerGame} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.getByText(/of 5 points/i)).toBeVisible();
  });

  it('given all achievements are worth 1 point, does not show the points progress', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 1 }), isObfuscated: false },
        { achievement: createAchievement({ points: 1 }), isObfuscated: false },
      ],
    });
    const playerGame = createPlayerGame({ achievementsUnlocked: 1, pointsHardcore: 1 });

    render(<EventProgress event={event} playerGame={playerGame} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.queryByText(/points/i)).not.toBeInTheDocument();
  });

  it('given the user has unlocked achievements but not earned awards, does not show the glow effect', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [createEventAward({ earnedAt: null })],
    });
    const playerGame = createPlayerGame({ achievementsUnlocked: 1 });

    render(<EventProgress event={event} playerGame={playerGame} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    expect(screen.queryByTestId('progress-blur')).not.toBeInTheDocument();
  });

  it('given the user has earned some awards but not all, shows the non-mastered glow effect', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [
        createEventAward({ earnedAt: '2023-01-01', pointsRequired: 5 }),
        createEventAward({ earnedAt: null, pointsRequired: 10 }),
      ],
    });
    const playerGame = createPlayerGame({ achievementsUnlocked: 1 });

    render(<EventProgress event={event} playerGame={playerGame} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    const glowElement = screen.getByTestId('progress-blur');
    expect(glowElement).toBeVisible();
    expect(glowElement).toHaveClass('from-zinc-400');
  });

  it('given the user has earned all awards, shows the mastered glow effect', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAwards: [
        createEventAward({ earnedAt: '2023-01-01' }),
        createEventAward({ earnedAt: '2023-01-02' }),
      ],
    });
    const playerGame = createPlayerGame({ achievementsUnlocked: 2 });

    render(<EventProgress event={event} playerGame={playerGame} />, {
      pageProps: { auth: { user: createAuthenticatedUser() } },
    });

    // ASSERT
    const glowElement = screen.getByTestId('progress-blur');
    expect(glowElement).toBeVisible();
    expect(glowElement).toHaveClass('from-yellow-400');
  });
});
