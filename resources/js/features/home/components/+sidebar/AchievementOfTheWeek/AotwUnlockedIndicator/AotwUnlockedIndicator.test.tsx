import { render, screen } from '@/test';
import { createAchievementOfTheWeekProps } from '@/test/factories';

import { AotwUnlockedIndicator } from './AotwUnlockedIndicator';

describe('Component: AotwUnlockedIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AotwUnlockedIndicator />, {
      pageProps: {
        achievementOfTheWeek: createAchievementOfTheWeekProps({
          doesUserHaveUnlock: false,
        }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no achievement of the week progress data, renders nothing', () => {
    // ARRANGE
    render(<AotwUnlockedIndicator />, {
      pageProps: {
        achievementOfTheWeek: null,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('aotw-progress')).not.toBeInTheDocument();
  });

  it('given the user has unlocked the current week, shows an unlocked message', () => {
    // ARRANGE
    render(<AotwUnlockedIndicator />, {
      pageProps: {
        achievementOfTheWeek: {
          doesUserHaveUnlock: true,
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/unlocked/i)).toBeVisible();
  });

  it('given the user has not unlocked the current week, renders nothing', () => {
    // ARRANGE
    render(<AotwUnlockedIndicator />, {
      pageProps: {
        achievementOfTheWeek: {
          doesUserHaveUnlock: false,
        },
      },
    });

    // ASSERT
    expect(screen.queryByTestId('aotw-progress')).not.toBeInTheDocument();
  });
});
