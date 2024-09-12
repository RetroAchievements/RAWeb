import { render, screen } from '@/test';
import { createAchievement, createGame, createSystem } from '@/test/factories';

import { AchievementBreadcrumbs } from './AchievementBreadcrumbs';

describe('Component: AchievementBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementBreadcrumbs currentPageLabel="Some Page" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Games list', () => {
    // ARRANGE
    render(<AchievementBreadcrumbs currentPageLabel="Some Page" />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all games/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', '/gameList.php');
  });

  it('given a system, has a link to the system games list', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(<AchievementBreadcrumbs currentPageLabel="Some Page" game={game} system={system} />);

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /nintendo 64/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `system.game.index,${system.id}`);
  });

  it('given a game, has a link to the game page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(<AchievementBreadcrumbs currentPageLabel="Some Page" game={game} system={system} />);

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: game.title });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });

  it('given an achievement, has a link to the achievement page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });
    const achievement = createAchievement();

    render(
      <AchievementBreadcrumbs
        currentPageLabel="Some Page"
        achievement={achievement}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const achievementLinkEl = screen.getByRole('link', { name: achievement.title });
    expect(achievementLinkEl).toBeVisible();
    expect(achievementLinkEl).toHaveAttribute(
      'href',
      `achievement.show,${{ achievement: achievement.id }}`,
    );
  });
});
