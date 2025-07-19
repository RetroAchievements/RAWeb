import { render, screen } from '@/test';
import { createAchievement, createSystem } from '@/test/factories';

import { AchievementResultDisplay } from './AchievementResultDisplay';

describe('Component: AchievementResultDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement();

    const { container } = render(<AchievementResultDisplay achievement={achievement} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the achievement badge with correct attributes', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'That Was Easy',
      badgeUnlockedUrl: 'https://example.com/badge.png',
    });

    render(<AchievementResultDisplay achievement={achievement} />);

    // ACT
    const badge = screen.getByRole('img');

    // ASSERT
    expect(badge).toBeVisible();
    expect(badge).toHaveAttribute('src', 'https://example.com/badge.png');
    expect(badge).toHaveAttribute('alt', 'That Was Easy');
  });

  it('displays the achievement title', () => {
    // ARRANGE
    const achievement = createAchievement({ title: 'That Was Easy' });
    render(<AchievementResultDisplay achievement={achievement} />);

    // ASSERT
    expect(screen.getByText(/that was easy/i)).toBeVisible();
  });

  it('displays the achievement points in parentheses', () => {
    // ARRANGE
    const achievement = createAchievement({ points: 50 });
    render(<AchievementResultDisplay achievement={achievement} />);

    // ASSERT
    expect(screen.getByText('(50)')).toBeVisible();
  });

  it('displays the associated game title', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: {
        id: 1,
        title: 'Super Mario World',
        badgeUrl: '',
        forumTopicId: 1,
        system: createSystem(),
      },
    });

    render(<AchievementResultDisplay achievement={achievement} />);

    // ASSERT
    expect(screen.getByText(/super mario world/i)).toBeVisible();
  });
});
