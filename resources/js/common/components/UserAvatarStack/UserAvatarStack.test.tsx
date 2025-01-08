import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createUser } from '@/test/factories';

import { UserAvatarStack } from './UserAvatarStack';

describe('Component: UserAvatarStack', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<UserAvatarStack users={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no users are provided, renders nothing', () => {
    // ARRANGE
    render(<UserAvatarStack users={[]} />);

    // ASSERT
    expect(screen.queryByRole('list')).not.toBeInTheDocument();
  });

  it('given a list of users is provided under the max visible limit, shows all users', () => {
    // ARRANGE
    const users = [createUser(), createUser(), createUser()];

    render(<UserAvatarStack users={users} maxVisible={5} />);

    // ASSERT
    const avatarList = screen.getByRole('list');
    // eslint-disable-next-line testing-library/no-node-access -- this is fine here, the count of nodes is relevant
    expect(avatarList.children).toHaveLength(3);
  });

  it('given more users than the max visible limit, shows the overflow indicator', () => {
    // ARRANGE
    const users = [
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser(), // !! 6th user
    ];

    render(<UserAvatarStack users={users} maxVisible={5} />);

    // ASSERT
    const avatarList = screen.getByRole('list');
    // eslint-disable-next-line testing-library/no-node-access -- this is fine here, the count of nodes is relevant
    expect(avatarList.children).toHaveLength(5); // !! 4 avatars + overflow
    expect(screen.getByText(/\+2/i)).toBeVisible();
  });

  it('given a size prop of 24, applies the correct size classes', () => {
    // ARRANGE
    const users = [
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser(), // !! 6th user
    ];

    render(<UserAvatarStack users={users} maxVisible={5} size={24} />);

    // ASSERT
    const overflowIndicator = screen.getByTestId('overflow-indicator');
    expect(overflowIndicator).toHaveClass('size-6');
  });

  it('given a size prop of 28, applies the correct size classes', () => {
    // ARRANGE
    const users = [
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser(), // !! 6th user
    ];

    render(<UserAvatarStack users={users} maxVisible={5} size={28} />);

    // ASSERT
    const overflowIndicator = screen.getByTestId('overflow-indicator');
    expect(overflowIndicator).toHaveClass('size-7');
  });

  it('given the user is using a non-English locale, formats the overflow list correctly', async () => {
    // ARRANGE
    const users = [
      createUser(),
      createUser(),
      createUser(),
      createUser(),
      createUser({ displayName: 'Scott' }),
      createUser({ displayName: 'TheMysticalOne' }),
      createUser({ displayName: 'Jamiras' }),
    ];

    render(<UserAvatarStack users={users} maxVisible={5} size={28} />, {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: 'pt_BR' }) } }, // !!
    });

    // ACT
    await userEvent.hover(screen.getByText(/\+3/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/Jamiras, Scott e TheMysticalOne/i)[0]).toBeVisible();
    });
  });
});
