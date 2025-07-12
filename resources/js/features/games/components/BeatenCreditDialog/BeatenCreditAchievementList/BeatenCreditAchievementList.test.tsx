import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { BeatenCreditAchievementList } from './BeatenCreditAchievementList';

describe('Component: BeatenCreditAchievementList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BeatenCreditAchievementList achievements={[]} type="progression" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the type is progression, displays the correct header text', () => {
    // ARRANGE
    const achievements = [createAchievement()];

    render(<BeatenCreditAchievementList achievements={achievements} type="progression" />);

    // ASSERT
    expect(screen.getByText(/progression achievements/i)).toBeVisible();
    expect(screen.getByText(/\(need all\)/i)).toBeVisible();
  });

  it('given the type is win_condition, displays the correct header text', () => {
    // ARRANGE
    const achievements = [createAchievement()];

    render(<BeatenCreditAchievementList achievements={achievements} type="win_condition" />);

    // ASSERT
    expect(screen.getByText(/win condition achievements/i)).toBeVisible();
    expect(screen.getByText(/\(need any\)/i)).toBeVisible();
  });

  it('given multiple achievements, renders all of them', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ id: 1, title: 'First Achievement' }),
      createAchievement({ id: 2, title: 'Second Achievement' }),
      createAchievement({ id: 3, title: 'Third Achievement' }),
    ];

    render(<BeatenCreditAchievementList achievements={achievements} type="progression" />);

    // ASSERT
    expect(screen.getByText(/first achievement/i)).toBeVisible();
    expect(screen.getByText(/second achievement/i)).toBeVisible();
    expect(screen.getByText(/third achievement/i)).toBeVisible();
  });

  it('given an achievement is unlocked, applies the correct styling for progression type', () => {
    // ARRANGE
    const unlockedAchievement = createAchievement({
      id: 1,
      title: 'Unlocked Achievement',
      unlockedAt: '2024-01-01T00:00:00.000Z',
    });

    render(<BeatenCreditAchievementList achievements={[unlockedAchievement]} type="progression" />);

    // ASSERT
    const unlockedContainer = screen.getByText(/unlocked achievement/i).closest('div.rounded-lg');
    expect(unlockedContainer).toHaveClass('border-green-700/30', 'bg-green-900/20');
  });

  it('given an achievement is unlocked, applies the correct styling for win_condition type', () => {
    // ARRANGE
    const unlockedAchievement = createAchievement({
      id: 1,
      title: 'Unlocked Achievement',
      unlockedAt: '2024-01-01T00:00:00.000Z',
    });

    render(
      <BeatenCreditAchievementList achievements={[unlockedAchievement]} type="win_condition" />,
    );

    // ASSERT
    const unlockedContainer = screen.getByText(/unlocked achievement/i).closest('div.rounded-lg');
    expect(unlockedContainer).toHaveClass('border-amber-700/30', 'bg-amber-900/20');
  });

  it('renders achievement descriptions', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ id: 1, description: 'Defeat the first boss' }),
      createAchievement({ id: 2, description: 'Collect all power-ups' }),
    ];

    render(<BeatenCreditAchievementList achievements={achievements} type="progression" />);

    // ASSERT
    expect(screen.getByText(/defeat the first boss/i)).toBeVisible();
    expect(screen.getByText(/collect all power-ups/i)).toBeVisible();
  });
});
