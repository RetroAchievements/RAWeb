import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { AchievementGamePanel } from './AchievementGamePanel';

describe('Component: AchievementGamePanel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame(),
    });

    const { container } = render(<AchievementGamePanel />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game title', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ title: 'Super Mario World' }),
    });

    render(<AchievementGamePanel />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/super mario world/i)).toBeVisible();
  });
});
