import i18n from '@/i18n-client';
import { render, screen } from '@/test';
import { createGame, createSystem, createUser } from '@/test/factories';

import { UserBreadcrumbs } from './UserBreadcrumbs';

describe('Component: UserBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Users list', () => {
    // ARRANGE
    render(<UserBreadcrumbs t_currentPageLabel={i18n.t('Comments')} />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all users/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', '/userList.php');
  });

  it('given a user, has a link to the user profile', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    render(<UserBreadcrumbs t_currentPageLabel={i18n.t('Comments')} user={user} />);

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /scott/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `user.show,${{ user: user.displayName }}`);
  });

  it('given a game, has a link to the game page', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });
    const user = createUser({ displayName: 'Scott' });

    render(<UserBreadcrumbs t_currentPageLabel={i18n.t('Comments')} game={game} user={user} />);

    // ASSERT
    const gameLinkEl = screen.getByRole('link', { name: game.title });
    expect(gameLinkEl).toBeVisible();
    expect(gameLinkEl).toHaveAttribute('href', `game.show,${{ game: game.id }}`);
  });
});
