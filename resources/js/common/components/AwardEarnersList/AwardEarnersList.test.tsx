import { render, screen } from '@/test';
import { createAwardEarner, createPaginatedData, createUser } from '@/test/factories';

import { AwardEarnersList } from './AwardEarnersList';

describe('Component: AwardEarnersList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const paginatedUsers = createPaginatedData([createAwardEarner()]);
    const { container } = render(<AwardEarnersList paginatedUsers={paginatedUsers} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no earners, renders nothing', () => {
    // ARRANGE
    const paginatedUsers = createPaginatedData([]);
    render(<AwardEarnersList paginatedUsers={paginatedUsers} />);

    // ASSERT
    expect(screen.queryByText('User')).not.toBeInTheDocument();
  });

  it('given there are earners, displays them', () => {
    // ARRANGE
    const user1 = createUser();
    const user2 = createUser();
    const paginatedUsers = createPaginatedData([
      createAwardEarner({ user: user1, dateEarned: '2024-11-01 05:55:55' }),
      createAwardEarner({ user: user2, dateEarned: '2024-12-17 19:31:18' }),
    ]);
    render(<AwardEarnersList paginatedUsers={paginatedUsers} />);

    // ASSERT
    expect(screen.getByText(user1.displayName)).toBeVisible();
    expect(screen.getByText('Nov 1, 2024 5:55 AM')).toBeVisible();
    expect(screen.getByText(user2.displayName)).toBeVisible();
    expect(screen.getByText('Dec 17, 2024 7:31 PM')).toBeVisible();
  });
});
