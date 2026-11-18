import { render } from '@/test';
import { createLeaderboardEntry, createUser } from '@/test/factories';

import { TicketLeaderboardEntryFact } from './TicketLeaderboardEntryFact';

describe('Component: TicketLeaderboardEntryFact', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketLeaderboardEntryFact reporter={createUser({ displayName: 'Scott' })} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a reporter entry, prints the formatted rank, total, and score', () => {
    // ARRANGE
    const { container } = render(
      <TicketLeaderboardEntryFact
        reporter={createUser({ displayName: 'Scott' })}
        entry={{
          leaderboardEntry: createLeaderboardEntry({ rank: 1234, formattedScore: '1:13.01' }),
          totalEntries: 20000,
        }}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott has an entry on this leaderboard: #1,234 of 20,000, 1:13.01.',
    );
  });

  it('given no reporter entry, states that the reporter has none yet', () => {
    // ARRANGE
    const { container } = render(
      <TicketLeaderboardEntryFact reporter={createUser({ displayName: 'Scott' })} />,
    );

    // ASSERT
    expect(container).toHaveTextContent('Scott has no entry yet on this leaderboard.');
  });
});
