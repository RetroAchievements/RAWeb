import { persistedAchievementsAtom } from '@/features/forums/state/forum.atoms';
import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { ShortcodeAch } from './ShortcodeAch';

describe('Component: ShortcodeAch', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeAch achievementId={1} />, {
      jotaiAtoms: [[persistedAchievementsAtom, []]],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no matching achievement is found, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });

    render(<ShortcodeAch achievementId={999} />, {
      jotaiAtoms: [[persistedAchievementsAtom, [achievement]]],
    });

    // ASSERT
    expect(screen.queryByTestId('achievement-embed')).not.toBeInTheDocument();
  });

  it('given a matching achievement is found, renders the achievement avatar', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, title: 'Test Achievement', points: 5 });

    render(<ShortcodeAch achievementId={1} />, {
      jotaiAtoms: [[persistedAchievementsAtom, [achievement]]],
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /test achievement/i })).toBeVisible();
    expect(screen.getAllByRole('link')[0]).toBeVisible();
    expect(screen.getByText('Test Achievement (5)')).toBeVisible();
  });
});
