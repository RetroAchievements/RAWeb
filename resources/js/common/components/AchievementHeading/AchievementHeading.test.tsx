import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { AchievementHeading } from './AchievementHeading';

describe('Component: AchievementHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AchievementHeading achievement={createAchievement()}>Hello, World</AchievementHeading>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a clickable avatar of the given achievement', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementHeading achievement={achievement}>Hello, World</AchievementHeading>);

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', `achievement.show,${{ game: achievement.id }}`);

    expect(screen.getByRole('img', { name: achievement.title })).toBeVisible();
  });

  it('displays an accessible header for `children`', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementHeading achievement={achievement}>Hello, World</AchievementHeading>);

    // ASSERT
    expect(screen.getByRole('heading', { name: /hello, world/i })).toBeVisible();
  });
});
