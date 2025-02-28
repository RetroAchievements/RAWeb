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

  it('given there is no title, shows a generic alt text on the image', () => {
    // ARRANGE
    const achievement = createAchievement({ title: undefined });

    render(<AchievementAvatar {...achievement} />);

    // ASSERT
    expect(screen.getByRole('img')).toHaveAttribute('alt', 'Achievement');
  });

  it('configures the image with correct loading attributes', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveAttribute('loading', 'lazy');
    expect(imgEl).toHaveAttribute('decoding', 'async');
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
    const anchorEl = screen.getAllByRole('link')[0];

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
    const anchorEl = screen.getAllByRole('link')[0];

    expect(anchorEl).not.toHaveAttribute('x-data');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseover');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseleave');
    expect(anchorEl).not.toHaveAttribute('x-on:mousemove');
  });

  it('can be configured to not show an image', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });

    render(<AchievementAvatar {...achievement} showImage={false} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('adds hardcore unlock border styling when displayLockedStatus is set to "unlocked-hardcore"', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} displayLockedStatus="unlocked-hardcore" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveClass(
      'rounded-[1px] outline outline-2 outline-offset-1 outline-[gold] light:outline-amber-500',
    );
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

  it('given a sublabelSlot, styles the content correctly', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementAvatar {...achievement} sublabelSlot={<span>Sublabel content</span>} />);

    // ASSERT
    const containerEl = screen.getByText(achievement.title).parentElement;
    expect(containerEl).toHaveClass('max-w-fit');
    expect(screen.getByText(/sublabel content/i)).toBeVisible();
  });

  it('given displayLockedStatus is not "unlocked-hardcore", applies the correct gap spacing', () => {
    // ARRANGE
    const achievement = createAchievement();

    // ACT
    render(<AchievementAvatar {...achievement} displayLockedStatus="unlocked" />);

    // ASSERT
    const containerEl = screen.getByText(achievement.title).parentElement?.parentElement;
    expect(containerEl).toHaveClass('gap-2');
    expect(containerEl).not.toHaveClass('gap-2.5');
  });

  it('given displayLockedStatus is not "unlocked-hardcore", does not apply border styling to the image', () => {
    // ARRANGE
    const achievement = createAchievement();

    // ACT
    render(<AchievementAvatar {...achievement} displayLockedStatus="unlocked" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).not.toHaveClass('outline-2');
  });

  it('given the variant is base, applies the correct classes', () => {
    // ARRANGE
    const achievement = createAchievement();

    // ACT
    render(<AchievementAvatar {...achievement} variant="base" />);

    // ASSERT
    const containerEl = screen.getByTestId('ach-avatar-root');
    expect(containerEl).toHaveClass('flex max-w-fit items-center');
    expect(containerEl).not.toHaveClass('inline-block min-h-[26px]');
  });

  it('given the variant is inline, applies the correct classes', () => {
    // ARRANGE
    const achievement = createAchievement();

    // ACT
    render(<AchievementAvatar {...achievement} variant="inline" />);

    // ASSERT
    const containerEl = screen.getByTestId('ach-avatar-root');
    expect(containerEl).toHaveClass('inline-block min-h-[26px]');
    expect(containerEl).not.toHaveClass('flex max-w-fit items-center');
  });

  it('given showLabel is false but the image should be shown, only renders the badge image with a link', () => {
    // ARRANGE
    const achievement = createAchievement({
      badgeUnlockedUrl: 'https://example.com/badge.png',
    });

    // ACT
    render(<AchievementAvatar {...achievement} showLabel={false} showImage={true} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');
    const imgEl = screen.getByRole('img');

    expect(screen.queryByText(achievement.title)).not.toBeInTheDocument();
    expect(anchorEl).toBeVisible();
    expect(imgEl).toBeVisible();
    expect(anchorEl).toContainElement(imgEl);
  });

  it('given title is undefined but showLabel is true, does not render a title link', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: undefined,
    });

    // ACT
    render(<AchievementAvatar {...achievement} showLabel={true} />);

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given badgeUnlockedUrl is undefined, does not render an image even if showImage is true', () => {
    // ARRANGE
    const achievement = createAchievement({
      badgeUnlockedUrl: undefined,
    });

    // ACT
    render(<AchievementAvatar {...achievement} showImage={true} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('given sublabelSlot exists but title is undefined, only renders the sublabel content with a flex column wrapper', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: undefined,
    });

    // ACT
    render(<AchievementAvatar {...achievement} sublabelSlot={<span>Sublabel content</span>} />);

    // ASSERT
    const sublabelEl = screen.getByText(/sublabel content/i);
    expect(sublabelEl.parentElement).toHaveClass('flex flex-col');
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given displayLockedStatus is auto and unlockedHardcoreAt exists, shows hardcore styling', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedHardcoreAt: '2024-02-22T12:00:00Z',
    });

    // ACT
    render(<AchievementAvatar {...achievement} displayLockedStatus="auto" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveClass(
      'rounded-[1px] outline outline-2 outline-offset-1 outline-[gold] light:outline-amber-500',
    );
  });

  it('given displayLockedStatus is auto and only unlockedAt exists, does not show hardcore styling', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-02-22T12:00:00Z',
      unlockedHardcoreAt: undefined,
    });

    // ACT
    render(<AchievementAvatar {...achievement} displayLockedStatus="auto" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).not.toHaveClass('outline-2');
  });

  it('given displayLockedStatus is auto and neither unlock timestamp exists, shows the locked badge', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
      badgeLockedUrl: 'https://example.com/locked.png',
      badgeUnlockedUrl: 'https://example.com/unlocked.png',
    });

    // ACT
    render(<AchievementAvatar {...achievement} displayLockedStatus="auto" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveAttribute('src', 'https://example.com/locked.png');
  });

  it('given displayLockedStatus is auto and achievement is unlocked normally, shows the unlocked badge', () => {
    // ARRANGE
    const achievement = createAchievement({
      unlockedAt: '2024-02-22T12:00:00Z',
      unlockedHardcoreAt: undefined,
      badgeLockedUrl: 'https://example.com/locked.png',
      badgeUnlockedUrl: 'https://example.com/unlocked.png',
    });

    // ACT
    render(<AchievementAvatar {...achievement} displayLockedStatus="auto" />);

    // ASSERT
    const imgEl = screen.getByRole('img');
    expect(imgEl).toHaveAttribute('src', 'https://example.com/unlocked.png');
  });
});
