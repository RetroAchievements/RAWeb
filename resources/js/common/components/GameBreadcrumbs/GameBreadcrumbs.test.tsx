import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import {
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createSystem,
} from '@/test/factories';

import { GameBreadcrumbs } from './GameBreadcrumbs';

describe('Component: GameBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Games list', () => {
    // ARRANGE
    render(<GameBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all games/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', 'game.index');
  });

  it('given a system, has a link to the system games list', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(<GameBreadcrumbs t_currentPageLabel={i18n.t('Comments')} game={game} system={system} />);

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /nintendo 64/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `system.game.index,${system.id}`);
  });

  it('given a game, has a link to the game page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render(<GameBreadcrumbs t_currentPageLabel={i18n.t('Comments')} game={game} system={system} />);

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: game.title });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });

  it('stylizes tags that are within game titles', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: '~Hack~ Super Junkoid' });

    render(<GameBreadcrumbs t_currentPageLabel={i18n.t('Comments')} game={game} system={system} />);

    // ASSERT
    expect(screen.queryByText('~')).not.toBeInTheDocument();

    const hackEl = screen.getByText(/hack/i);
    expect(hackEl).toBeVisible();
    expect(hackEl.nodeName).toEqual('SPAN');
  });

  it('given a gameAchievementSet with t_currentPageLabel, renders achievement set as link', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ system });
    const gameAchievementSet = createGameAchievementSet({
      title: 'Bonus Set',
      achievementSet: createAchievementSet({ id: 123 }),
    });

    render(
      <GameBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')} // !! makes achievement set a link
        game={game}
        system={system}
        gameAchievementSet={gameAchievementSet}
      />,
    );

    // ASSERT
    const achievementSetLinkEl = screen.getByRole('link', { name: /bonus set/i });
    expect(achievementSetLinkEl).toBeVisible();
    expect(achievementSetLinkEl).toHaveAttribute(
      'href',
      `game2.show,${{ game: game.id, set: 123 }}`,
    );
  });

  it('given a gameAchievementSet without t_currentPageLabel, renders achievement set as current page', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ system });
    const gameAchievementSet = createGameAchievementSet({
      title: 'Bonus Set',
      achievementSet: createAchievementSet({ id: 123 }),
    });

    render(
      <GameBreadcrumbs
        game={game}
        system={system}
        gameAchievementSet={gameAchievementSet} // !! no t_currentPageLabel makes this the current page
      />,
    );

    // ASSERT
    expect(screen.getByText('Bonus Set')).toBeVisible();
    // BaseBreadcrumbPage renders with role="link" but aria-disabled="true"
    const breadcrumbPageEl = screen.getByRole('link', { name: /bonus set/i });
    expect(breadcrumbPageEl).toHaveAttribute('aria-disabled', 'true');
  });

  it('given game with t_currentPageLabel, renders game as link', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: 'Super Mario World', system });

    render(
      <GameBreadcrumbs
        t_currentPageLabel={i18n.t('Comments')} // !! makes game a link
        game={game}
        system={system}
      />,
    );

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: /super mario world/i });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });

  it('given game without t_currentPageLabel and no gameAchievementSet, renders game as current page', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: 'Super Mario World', system });

    render(
      <GameBreadcrumbs
        game={game}
        system={system} // !! no t_currentPageLabel and no gameAchievementSet makes game the current page
      />,
    );

    // ASSERT
    expect(screen.getByText('Super Mario World')).toBeVisible();
    // BaseBreadcrumbPage renders with role="link" but aria-disabled="true"
    const breadcrumbPageEl = screen.getByRole('link', { name: /super mario world/i });
    expect(breadcrumbPageEl).toHaveAttribute('aria-disabled', 'true');
  });

  it('given game with gameAchievementSet but no t_currentPageLabel, renders game as link', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ title: 'Super Mario World', system });
    const gameAchievementSet = createGameAchievementSet({
      title: 'Bonus Set',
      achievementSet: createAchievementSet({ id: 123 }),
    });

    render(
      <GameBreadcrumbs
        game={game}
        system={system}
        gameAchievementSet={gameAchievementSet} // !! gameAchievementSet present makes game a link
      />,
    );

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: /super mario world/i });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });

  it('renders current page label when provided', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({ system });

    render(
      <GameBreadcrumbs
        t_currentPageLabel={i18n.t('Achievements')} // !! current page label is rendered last
        game={game}
        system={system}
      />,
    );

    // ASSERT
    expect(screen.getByText('Achievements')).toBeVisible();
    // BaseBreadcrumbPage renders with role="link" but aria-disabled="true"
    const breadcrumbPageEl = screen.getByRole('link', { name: /achievements/i });
    expect(breadcrumbPageEl).toHaveAttribute('aria-disabled', 'true');
  });

  it('renders only All Games link when no other props provided', () => {
    // ARRANGE
    render(<GameBreadcrumbs />); // !! minimal props case

    // ASSERT
    expect(screen.getByRole('link', { name: /all games/i })).toBeVisible();
    expect(screen.queryByRole('link', { name: /nintendo/i })).not.toBeInTheDocument();
  });

  it('does not render system breadcrumb when system is missing but game is present', () => {
    // ARRANGE
    const game = createGame({ title: 'Standalone Game' });

    render(
      <GameBreadcrumbs
        game={game} // !! game without system
        t_currentPageLabel={i18n.t('Comments')}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link', { name: /all games/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /standalone game/i })).toBeVisible();
    expect(screen.queryByRole('link', { name: /nintendo/i })).not.toBeInTheDocument();
  });
});
