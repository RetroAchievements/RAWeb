import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { AchievementShowSidebarRoot } from './AchievementShowSidebarRoot';

describe('Component: AchievementShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame(),
    });

    const { container } = render(<AchievementShowSidebarRoot />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game panel', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ title: 'Sonic the Hedgehog' }),
    });

    render(<AchievementShowSidebarRoot />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/sonic the hedgehog/i)).toBeVisible();
  });
});
