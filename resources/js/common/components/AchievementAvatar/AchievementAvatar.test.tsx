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

  it('does not add card tooltip props when `hasTooltip` is false', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });

    render(<AchievementAvatar {...achievement} hasTooltip={false} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).not.toHaveAttribute('x-data');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseover');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseleave');
    expect(anchorEl).not.toHaveAccessibleDescription('x-on:mousemove');
  });

  it('can be configured to not show an image', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });

    render(<AchievementAvatar {...achievement} showImage={false} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('adds hardcore unlock border styling when showHardcoreUnlockBorder is true', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} showHardcoreUnlockBorder={true} />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveClass('outline outline-2 outline-offset-1 outline-[gold]');
  });

  it('does not add hardcore unlock border styling when showHardcoreUnlockBorder is false', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} showHardcoreUnlockBorder={false} />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).not.toHaveClass('outline outline-2 outline-offset-1 outline-[gold]');
  });

  it('given showPointsInTitle is true, includes points in the title text', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Test Achievement',
      points: 10,
    });

    render(<AchievementAvatar {...achievement} showPointsInTitle={true} />);

    // ASSERT
    expect(screen.getByText('Test Achievement (10)')).toBeVisible();
  });

  it('given showPointsInTitle is true but points are undefined, shows zero points', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Test Achievement',
      points: undefined,
    });

    render(<AchievementAvatar {...achievement} showPointsInTitle={true} />);

    // ASSERT
    expect(screen.getByText('Test Achievement (0)')).toBeVisible();
  });

  it('given a sublabelSlot, wraps the content in a flex column container', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} sublabelSlot={<span>Sublabel content</span>} />);

    // ASSERT
    const containerEl = screen.getByText(achievement.title).parentElement;

    expect(containerEl).toHaveClass('flex flex-col');
    expect(screen.getByText(/sublabel content/i)).toBeVisible();
  });

  it('given showLabel is false with sublabelSlot, does not show the title text', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(
      <AchievementAvatar {...achievement} showLabel={false} sublabelSlot={<span>Sublabel</span>} />,
    );

    // ASSERT
    expect(screen.queryByText(achievement.title)).not.toBeInTheDocument();
    expect(screen.getByText(/sublabel/i)).toBeVisible();
  });
});
