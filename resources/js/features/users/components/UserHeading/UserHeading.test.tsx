import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { UserHeading } from './UserHeading';

describe('Component: UserHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserHeading user={createUser()}>Hello, World</UserHeading>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a clickable avatar of the given user', () => {
    // ARRANGE
    const user = createUser();

    render(<UserHeading user={user}>Hello, World</UserHeading>);

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', `user.show,${user.displayName}`);

    expect(screen.getByRole('img', { name: user.displayName })).toBeVisible();
  });

  it('displays an accessible header from `children`', () => {
    // ARRANGE
    const user = createUser();

    render(<UserHeading user={user}>Hello, World</UserHeading>);

    // ASSERT
    expect(screen.getByRole('heading', { name: /hello, world/i })).toBeVisible();
  });
});
