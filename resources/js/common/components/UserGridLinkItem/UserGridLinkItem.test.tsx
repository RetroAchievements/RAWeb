import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { UserGridLinkItem } from './UserGridLinkItem';

describe('Component: UserGridLinkItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const supporter = createUser();
    const { container } = render(<UserGridLinkItem user={supporter} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a supporter, renders a link to their profile', () => {
    // ARRANGE
    const supporter = createUser({ displayName: 'TestUser123' });
    render(<UserGridLinkItem user={supporter} />);

    // ACT
    const profileLink = screen.getByRole('link');

    // ASSERT
    expect(profileLink).toBeVisible();
    expect(profileLink).toHaveAttribute('href', expect.stringContaining('user.show'));
  });

  it('given a supporter, renders their avatar', () => {
    // ARRANGE
    const supporter = createUser({
      displayName: 'TestUser123',
      avatarUrl: 'https://example.com/avatar.png',
    });
    render(<UserGridLinkItem user={supporter} />);

    // ACT
    const avatar = screen.getByRole('img', { name: /testuser123/i });

    // ASSERT
    expect(avatar).toBeVisible();
    expect(avatar).toHaveAttribute('src', 'https://example.com/avatar.png');
  });
});
