import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { UnlockableAchievementAvatar } from './UnlockableAchievementAvatar';

describe('Component: UnlockableAchievementAvatar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement();
    const { container } = render(<UnlockableAchievementAvatar achievement={achievement} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a locked achievement correctly', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Creative Name',
      description: 'Do the thing',
    });

    render(<UnlockableAchievementAvatar achievement={achievement} />);

    // ASSERT
    const img = screen.getByRole('img') as HTMLImageElement;
    expect(img).toBeVisible();
    expect(img.src).toContain(achievement.badgeLockedUrl);

    expect(screen.getByText(/Creative Name/)).toBeVisible();
    expect(screen.getByText(/Do the thing/)).toBeVisible();
    expect(screen.queryByText(/unlocked/i)).toBeNull();
  });

  it('displays a softcore unlock correctly', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Creative Name',
      description: 'Do the thing',
      unlockedAt: '2024-08-12 16:24:36',
    });

    render(<UnlockableAchievementAvatar achievement={achievement} />);

    // ASSERT
    const img = screen.getByRole('img') as HTMLImageElement;
    expect(img).toBeVisible();
    expect(img.src).toContain(achievement.badgeUnlockedUrl);

    expect(screen.getByText(/Creative Name/)).toBeVisible();
    expect(screen.getByText(/Unlocked Aug 12, 2024 4:24 PM/)).toBeVisible();
  });

  it('displays a hardcore unlock correctly', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Creative Name',
      description: 'Do the thing',
      unlockedAt: '2024-08-12 16:24:36',
      unlockedHardcoreAt: '2024-09-05 08:11:42',
    });

    render(<UnlockableAchievementAvatar achievement={achievement} />);

    // ASSERT
    const img = screen.getByRole('img') as HTMLImageElement;
    expect(img).toBeVisible();
    expect(img.src).toContain(achievement.badgeUnlockedUrl);

    expect(screen.getByText(/Creative Name/)).toBeVisible();
    expect(screen.getByText(/Unlocked Sep 5, 2024 8:11 AM/)).toBeVisible();
  });

  it('displays a hardcore unlock correctly', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Creative Name',
      description: 'Do the thing',
      unlockedAt: '2024-08-12 16:24:36',
      unlockedHardcoreAt: '2024-09-05 08:11:42',
    });

    render(<UnlockableAchievementAvatar achievement={achievement} />);

    // ASSERT
    const img = screen.getByRole('img') as HTMLImageElement;
    expect(img).toBeVisible();
    expect(img.src).toContain(achievement.badgeUnlockedUrl);

    expect(screen.getByText(/Creative Name/)).toBeVisible();
    expect(screen.getByText(/Unlocked Sep 5, 2024 8:11 AM/)).toBeVisible();
  });

  it('displays the game correctly', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Creative Name',
      description: 'Do the thing',
      game: createGame({ title: 'Container Set' }),
    });

    render(<UnlockableAchievementAvatar achievement={achievement} showGame={true} />);

    // ASSERT
    const img = screen.getByRole('img') as HTMLImageElement;
    expect(img).toBeVisible();
    expect(img.src).toContain(achievement.badgeLockedUrl);

    expect(screen.getAllByText(/Creative Name/)[0]).toBeVisible();
    expect(screen.getAllByText(/Container Set/)[0]).toBeVisible();
    expect(screen.getByText(/Do the thing/)).toBeVisible();
    expect(screen.queryByText(/unlocked/i)).toBeNull();
  });

  it('hides the game correctly', () => {
    // ARRANGE
    const achievement = createAchievement({
      title: 'Creative Name',
      description: 'Do the thing',
      game: createGame({ title: 'Container Set' }),
    });

    render(<UnlockableAchievementAvatar achievement={achievement} showGame={false} />);

    // ASSERT
    const img = screen.getByRole('img') as HTMLImageElement;
    expect(img).toBeVisible();
    expect(img.src).toContain(achievement.badgeLockedUrl);

    expect(screen.getByText(/Creative Name/)).toBeVisible();
    expect(screen.queryByText(/Container Set/)).toBeNull();
    expect(screen.getByText(/Do the thing/)).toBeVisible();
    expect(screen.queryByText(/unlocked/i)).toBeNull();
  });
});
