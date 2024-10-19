import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { UserBreadcrumbs } from './UserBreadcrumbs';

describe('Component: UserBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserBreadcrumbs t_currentPageLabel="Some Page" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Users list', () => {
    // ARRANGE
    render(<UserBreadcrumbs t_currentPageLabel="Some Page" />);

    // ASSERT
    const allGamesLinkEl = screen.getByRole('link', { name: /all users/i });
    expect(allGamesLinkEl).toBeVisible();
    expect(allGamesLinkEl).toHaveAttribute('href', '/userList.php');
  });

  it('given a user, has a link to the user profile', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    render(<UserBreadcrumbs t_currentPageLabel="Some Page" user={user} />);

    // ASSERT
    const systemGamesLinkEl = screen.getByRole('link', { name: /scott/i });
    expect(systemGamesLinkEl).toBeVisible();
    expect(systemGamesLinkEl).toHaveAttribute('href', `user.show,${{ user: user.displayName }}`);
  });
});
