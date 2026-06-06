import { render, screen } from '@/test';
import {
  createAchievement,
  createEventAchievement,
  createGame,
  createSystem,
} from '@/test/factories';

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

  it('given an event game with event data, shows event info instead of meta details', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame(),
    });

    render(<AchievementShowSidebarRoot />, {
      pageProps: {
        achievement,
        isEventGame: true,
        eventAchievement: createEventAchievement({
          sourceAchievement: createAchievement({
            game: createGame({ title: 'Action Man: Robot Atak', system: createSystem() }),
          }),
          activeFrom: '2025-01-06',
          activeThrough: '2025-01-12',
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/action man: robot atak/i)).toBeVisible();
    expect(screen.queryByText(/created by/i)).not.toBeInTheDocument();
  });
});
