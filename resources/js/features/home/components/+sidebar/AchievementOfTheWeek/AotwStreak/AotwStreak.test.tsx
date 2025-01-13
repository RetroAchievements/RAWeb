import { render, screen } from '@/test';
import { createAchievementOfTheWeekProps } from '@/test/factories';

import { AotwStreak } from './AotwStreak';

describe('Component: AotwStreak', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AotwStreak />, {
      pageProps: {
        achievementOfTheWeek: createAchievementOfTheWeekProps({
          achievementOfTheWeekProgress: {
            streakLength: 1,
            hasCurrentWeek: true,
            hasActiveStreak: true,
          },
        }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no achievement of the week progress data, renders nothing', () => {
    // ARRANGE
    render(<AotwStreak />, {
      pageProps: {
        achievementOfTheWeek: null,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('aotw-progress')).not.toBeInTheDocument();
  });

  it('given the user has unlocked the current week and has a multi-week streak, shows the streak length', () => {
    // ARRANGE
    render(<AotwStreak />, {
      pageProps: {
        achievementOfTheWeek: {
          achievementOfTheWeekProgress: {
            streakLength: 3,
            hasCurrentWeek: true,
            hasActiveStreak: true,
          },
        },
      },
    });

    // ASSERT
    expect(screen.getByTestId('aotw-progress')).toBeVisible();
    expect(screen.getByText(/unlocked. 3 weeks in a row!/i)).toBeVisible();
  });

  it('given the user has only unlocked the current week, shows a simple unlocked message', () => {
    // ARRANGE
    render(<AotwStreak />, {
      pageProps: {
        achievementOfTheWeek: {
          achievementOfTheWeekProgress: {
            streakLength: 1,
            hasCurrentWeek: true,
            hasActiveStreak: true,
          },
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/unlocked\./i)).toBeVisible();
  });

  it('given the user has an active streak but has not unlocked the current week, shows the streak extension message', () => {
    // ARRANGE
    render(<AotwStreak />, {
      pageProps: {
        achievementOfTheWeek: {
          achievementOfTheWeekProgress: {
            streakLength: 2,
            hasCurrentWeek: false,
            hasActiveStreak: true,
          },
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/extend your 2 week streak!/i)).toBeVisible();
  });

  it('given the user has no active streak and has not unlocked the current week, renders nothing', () => {
    // ARRANGE
    render(<AotwStreak />, {
      pageProps: {
        achievementOfTheWeek: {
          achievementOfTheWeekProgress: {
            streakLength: 0,
            hasCurrentWeek: false,
            hasActiveStreak: false,
          },
        },
      },
    });

    // ASSERT
    expect(screen.queryByTestId('aotw-progress')).not.toBeInTheDocument();
  });
});
