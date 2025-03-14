import { render, screen } from '@/test';
import { createAchievement, createEventAchievement } from '@/test/factories';

import { AchievementSet } from './AchievementSet';

describe('Component: AchievementSet', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AchievementSet achievements={[]} currentSort="active" playersTotal={0} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an empty achievements array, renders an empty list', () => {
    // ARRANGE
    render(<AchievementSet achievements={[]} currentSort="active" playersTotal={0} />);

    // ASSERT
    expect(screen.queryByRole('listitem')).not.toBeInTheDocument();
  });

  it('given the current sort is not "active", renders achievements in a single list', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ id: 1, title: 'Achievement 1' }),
      createAchievement({ id: 2, title: 'Achievement 2' }),
    ];

    render(
      <AchievementSet achievements={achievements} currentSort="displayOrder" playersTotal={100} />,
    );

    // ASSERT
    const listItems = screen.getAllByRole('listitem');
    expect(listItems).toHaveLength(2);
  });

  it('given the current sort is "active", groups achievements by status', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ id: 1, title: 'Active Achievement' }),
      createAchievement({ id: 2, title: 'Expired Achievement' }),
    ] as App.Platform.Data.Achievement[];

    const eventAchievements = [
      createEventAchievement({
        achievement: achievements[0],
        activeThrough: new Date(Date.now() + 86400000).toISOString(),
      }),
      createEventAchievement({
        achievement: achievements[1],
        activeThrough: new Date(Date.now() - 86400000).toISOString(),
      }),
    ];

    render(
      <AchievementSet
        achievements={achievements}
        currentSort="active"
        eventAchievements={eventAchievements}
        playersTotal={100}
      />,
    );

    // ASSERT
    expect(screen.getByText(/active/i)).toBeVisible();
    expect(screen.getByText(/expired/i)).toBeVisible();
  });

  it('given an achievement list longer than 50 items, does not crash', () => {
    // ARRANGE
    const achievements = Array.from({ length: 51 }, (_, i) =>
      createAchievement({
        id: i + 1,
        title: `Achievement ${i + 1}`,
      }),
    );

    const { container } = render(
      <AchievementSet achievements={achievements} currentSort="displayOrder" playersTotal={100} />,
    );

    // ASSERT
    expect(screen.getAllByRole('listitem')).toHaveLength(51);
    expect(container).toBeTruthy();
  });
});
