import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import { createGame, createLeaderboard, createSystem } from '@/test/factories';

import { LeaderboardBreadcrumbs } from './LeaderboardBreadcrumbs';

describe('Component: LeaderboardBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <LeaderboardBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Games list', () => {
    // ARRANGE
    render(<LeaderboardBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all games/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', 'game.index');
  });

  it('given a system, has a link to the system games list', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(
      <LeaderboardBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /nintendo 64/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `system.game.index,${system.id}`);
  });

  it('given a game, has a link to the game page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(
      <LeaderboardBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: game.title });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });

  it('given a leaderboard, has a link to the leaderboard page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });
    const leaderboard = createLeaderboard();

    render(
      <LeaderboardBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        leaderboard={leaderboard}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const leaderboardLinkEl = screen.getByRole('link', { name: leaderboard.title });
    expect(leaderboardLinkEl).toBeVisible();
    expect(leaderboardLinkEl).toHaveAttribute(
      'href',
      `leaderboard.show,${{ leaderboard: leaderboard.id }}`,
    );
  });

  it('stylizes tags that are within game titles', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: '~Hack~ Super Junkoid' });
    const leaderboard = createLeaderboard();

    render(
      <LeaderboardBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')}
        leaderboard={leaderboard}
        game={game}
        system={system}
      />,
    );

    // ASSERT
    expect(screen.queryByText('~')).not.toBeInTheDocument();

    const hackEl = screen.getByText(/hack/i);
    expect(hackEl).toBeVisible();
    expect(hackEl.nodeName).toEqual('SPAN');
  });
});
