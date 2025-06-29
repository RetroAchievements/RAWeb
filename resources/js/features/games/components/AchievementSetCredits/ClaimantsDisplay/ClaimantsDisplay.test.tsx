import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createAchievementSetClaim, createUser } from '@/test/factories';

import { ClaimantsDisplay } from './ClaimantsDisplay';

describe('Component: ClaimantsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ClaimantsDisplay achievementSetClaims={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given achievement set claims exist, shows user avatars', () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({ user: createUser({ displayName: 'Alice' }) }),
      createAchievementSetClaim({ user: createUser({ displayName: 'Bob' }) }),
    ];

    render(<ClaimantsDisplay achievementSetClaims={achievementSetClaims} />);

    // ASSERT
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(2);
  });

  it('shows the claims icon with tooltip on hover', async () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        finishedAt: '2025-12-31T23:59:59Z',
      }),
      createAchievementSetClaim({
        user: createUser({ displayName: 'Bob' }),
        finishedAt: '2025-06-30T23:59:59Z',
      }),
    ];

    render(<ClaimantsDisplay achievementSetClaims={achievementSetClaims} />);

    // ACT
    await userEvent.hover(screen.getAllByRole('button')[0]);

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/active claims/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Alice')[0]).toBeVisible();
    expect(screen.getAllByText('Bob')[0]).toBeVisible();
    expect(screen.getAllByText(/expires/i)[0]).toBeVisible();
  });

  it('given claims with userLastPlayedAt, shows the calendar icon', () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        userLastPlayedAt: '2025-01-15T10:30:00Z', // !! has last played date
      }),
      createAchievementSetClaim({
        user: createUser({ displayName: 'Bob' }),
        userLastPlayedAt: '2025-01-15T10:30:00Z', // !! has last played date
      }),
    ];

    render(<ClaimantsDisplay achievementSetClaims={achievementSetClaims} />);

    // ASSERT
    // ... should show calendar icon since at least one claim has userLastPlayedAt ...
    expect(screen.getByRole('button', { name: '' })).toBeInTheDocument();
  });

  it('given no claims have userLastPlayedAt, does not show the calendar icon', () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        userLastPlayedAt: null, // !! no last played date
      }),
      createAchievementSetClaim({
        user: createUser({ displayName: 'Bob' }),
        userLastPlayedAt: null, // !! no last played date
      }),
    ];

    render(<ClaimantsDisplay achievementSetClaims={achievementSetClaims} />);

    // ASSERT
    // ... should only have one button (the claims icon), not the calendar icon ...
    const buttons = screen.getAllByRole('button');
    expect(buttons).toHaveLength(1);
  });

  it('shows last played dates in descending order when hovering calendar icon', async () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({
        user: createUser({ displayName: 'Alice' }),
        userLastPlayedAt: '2025-01-10T10:00:00Z', // !! older date
      }),
      createAchievementSetClaim({
        user: createUser({ displayName: 'Bob' }),
        userLastPlayedAt: '2025-01-20T10:00:00Z', // !! newer date
      }),
      createAchievementSetClaim({
        user: createUser({ displayName: 'Charlie' }),
        userLastPlayedAt: '2025-01-15T10:00:00Z', // !! middle date
      }),
    ];

    render(<ClaimantsDisplay achievementSetClaims={achievementSetClaims} />);

    // ACT
    const buttons = screen.getAllByRole('button');
    await userEvent.hover(buttons[1]); // calendar icon is the second button

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/last played/i)[0]).toBeVisible();
    });

    // ... check that users appear in the correct order (most recent first) ...
    const tooltipRows = screen.getAllByText(/bob|charlie|alice/i);
    expect(tooltipRows[0]).toHaveTextContent('Bob');
    expect(tooltipRows[1]).toHaveTextContent('Charlie');
    expect(tooltipRows[2]).toHaveTextContent('Alice');
  });

  it('shows "Claimed by" text on larger screens', () => {
    // ARRANGE
    const achievementSetClaims = [
      createAchievementSetClaim({ user: createUser({ displayName: 'Alice' }) }),
    ];

    render(<ClaimantsDisplay achievementSetClaims={achievementSetClaims} />);

    // ASSERT
    expect(screen.getByText(/claimed by/i)).toBeInTheDocument();
  });
});
