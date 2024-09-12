import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { AchievementAvatar } from './AchievementAvatar';

describe('Component: AchievementAvatar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementAvatar {...createAchievement()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an achievement title, shows the achievement title on the screen', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} />);

    // ASSERT
    expect(screen.getByText(achievement.title)).toBeVisible();
  });

  it('given there is no title, still renders successfully', () => {
    // ARRANGE
    const achievement = createAchievement({ title: undefined });

    render(<AchievementAvatar {...achievement} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /achievement/i })).toBeVisible();
  });

  it('applies the correct size to the image', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} size={8} />);

    // ASSERT
    const imgEl = screen.getByRole('img');

    expect(imgEl).toHaveAttribute('width', '8');
    expect(imgEl).toHaveAttribute('height', '8');
  });

  it('adds card tooltip props by default', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });

    render(<AchievementAvatar {...achievement} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).toHaveAttribute(
      'x-data',
      "tooltipComponent($el, {dynamicType: 'achievement', dynamicId: '1', dynamicContext: 'undefined'})",
    );
    expect(anchorEl).toHaveAttribute('x-on:mouseover', 'showTooltip($event)');
    expect(anchorEl).toHaveAttribute('x-on:mouseleave', 'hideTooltip');
    expect(anchorEl).toHaveAttribute('x-on:mousemove', 'trackMouseMovement($event)');
  });
});
