import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createSetUserRequestInfo, createUser } from '@/test/factories';

import { UserRequestStatistics } from './UserRequestStatistics';

describe('Component: UserRequestStatistics', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const targetUser = createUser();
    const userRequestInfo = createSetUserRequestInfo({
      used: 5,
      total: 10,
      pointsForNext: 100,
    });

    const { container } = render(
      <UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct request usage statistics', () => {
    // ARRANGE
    const targetUser = createUser();
    const userRequestInfo = createSetUserRequestInfo({
      used: 3,
      total: 15,
      pointsForNext: 50,
    });

    render(<UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />);

    // ASSERT
    expect(screen.getByText(/3 of 15 requests made/i)).toBeVisible();
  });

  it('given the user is viewing their own requests and has points until next request, shows the points message', () => {
    // ARRANGE
    const currentUser = createAuthenticatedUser({ displayName: 'CurrentUser' });
    const targetUser = createUser({ displayName: 'CurrentUser' });
    const userRequestInfo = createSetUserRequestInfo({
      used: 5,
      total: 10,
      pointsForNext: 250,
    });

    render(<UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />, {
      pageProps: {
        auth: { user: currentUser },
      },
    });

    // ASSERT
    expect(screen.getByText(/5 of 10 requests made/i)).toBeVisible();
    expect(screen.getByText(/250 points until you earn another request/i)).toBeVisible();
  });

  it('given the user is viewing their own requests but has zero points until next request, does not show the points message', () => {
    // ARRANGE
    const currentUser = createAuthenticatedUser({ displayName: 'CurrentUser' });
    const targetUser = createUser({ displayName: 'CurrentUser' });
    const userRequestInfo = createSetUserRequestInfo({
      used: 10,
      total: 10,
      pointsForNext: 0,
    });

    render(<UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />, {
      pageProps: {
        auth: { user: currentUser },
      },
    });

    // ASSERT
    expect(screen.getByText(/10 of 10 requests made/i)).toBeVisible();
    expect(screen.queryByText(/points until you earn another request/i)).not.toBeInTheDocument();
  });

  it("given the user is viewing another user's requests, does not show the points message", () => {
    // ARRANGE
    const currentUser = createAuthenticatedUser({ displayName: 'CurrentUser' });
    const targetUser = createUser({ displayName: 'AnotherUser' });
    const userRequestInfo = createSetUserRequestInfo({
      used: 7,
      total: 20,
      pointsForNext: 500, // !! even with points, it shouldn't show.
    });

    render(<UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />, {
      pageProps: {
        auth: { user: currentUser },
      },
    });

    // ASSERT
    expect(screen.getByText(/7 of 20 requests made/i)).toBeVisible();
    expect(screen.queryByText(/points until you earn another request/i)).not.toBeInTheDocument();
  });

  it('given there is no authenticated user, does not show the points message', () => {
    // ARRANGE
    const targetUser = createUser({ displayName: 'SomeUser' });
    const userRequestInfo = createSetUserRequestInfo({
      used: 2,
      total: 8,
      pointsForNext: 150,
    });

    render(<UserRequestStatistics targetUser={targetUser} userRequestInfo={userRequestInfo} />, {
      pageProps: {
        auth: null,
      },
    });

    // ASSERT
    expect(screen.getByText(/2 of 8 requests made/i)).toBeVisible();
    expect(screen.queryByText(/points until you earn another request/i)).not.toBeInTheDocument();
  });
});
