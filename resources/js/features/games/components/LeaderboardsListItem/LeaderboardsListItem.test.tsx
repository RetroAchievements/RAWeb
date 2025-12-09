import { render, screen, waitFor } from '@/test';
import { createLeaderboard, createLeaderboardEntry, createUser } from '@/test/factories';
import userEvent from '@testing-library/user-event';

import { LeaderboardsListItem } from './LeaderboardsListItem';

describe('Component: LeaderboardsListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const leaderboard = createLeaderboard();

    const { container } = render(
      <LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a leaderboard, displays the title and description', async () => {
    // ARRANGE
    const leaderboard = createLeaderboard({
      title: 'High Score Challenge',
      description: 'Get the highest score possible',
    });

    render(<LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/high score challenge/i)).toBeVisible();
    });

    expect(screen.getByText(/get the highest score possible/i)).toBeVisible();
  });

  it('given a leaderboard, renders links with correct hrefs', async () => {
    // ARRANGE
    const leaderboard = createLeaderboard({ id: 12345 });

    render(<LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />);

    // ACT
    const links = screen.getAllByRole('link');

    // ASSERT
    await waitFor(() => {
      expect(links).toHaveLength(2);
    });

    expect(links[0]).toHaveAttribute('href', '/leaderboardinfo.php?i=12345');
    expect(links[1]).toHaveAttribute('href', '/leaderboardinfo.php?i=12345');
  });

  it('given a leaderboard with a top entry, displays their information', async () => {
    // ARRANGE
    const topUser = createUser({ displayName: 'TopPlayer' });
    const topEntry = createLeaderboardEntry({
      user: topUser,
      formattedScore: '999,999',
    });
    const leaderboard = createLeaderboard({ topEntry });

    render(<LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/topplayer/i)).toBeVisible();
    });

    expect(screen.getByText(/999,999/i)).toBeVisible();
  });

  it('given a leaderboard without a top entry user, does not crash', () => {
    // ARRANGE
    const topEntry = createLeaderboardEntry({ user: null });
    const leaderboard = createLeaderboard({ topEntry });

    const { container } = render(
      <LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a leaderboard without any top entry, does not crash', () => {
    // ARRANGE
    const leaderboard = createLeaderboard({ topEntry: undefined });

    const { container } = render(
      <LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given isLargeList is true, does not crash', () => {
    // ARRANGE
    const leaderboard = createLeaderboard();

    const { container } = render(
      <LeaderboardsListItem index={10} isLargeList={true} leaderboard={leaderboard} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is a user entry for the leaderboard, displays it', async () => {
    // ARRANGE
    const leaderboard = createLeaderboard({
      rankAsc: false,
      userEntry: createLeaderboardEntry({
        rank: 2,
        score: 200,
        formattedScore: '200',
      }),
      topEntry: createLeaderboardEntry({ score: 200 }),
    });

    render(<LeaderboardsListItem index={10} isLargeList={true} leaderboard={leaderboard} />);

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText('#2')).toBeVisible();
    });
    expect(screen.getByText(/200/i)).toBeVisible();
  });

  it('given the leaderboard is disabled, shows a tooltip', async () => {
    // ARRANGE
    const user = userEvent.setup();
    const leaderboard = createLeaderboard({
      state: 'disabled',
    });

    render(<LeaderboardsListItem index={0} isLargeList={false} leaderboard={leaderboard} />);

    // ACT
    await user.hover(screen.getByTestId('disabled-tooltip-trigger'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('tooltip')).toBeVisible();
      expect(screen.getByRole('tooltip')).toHaveTextContent(/this leaderboard is currently disabled and not accepting new entries/i);
    });
  });
});
