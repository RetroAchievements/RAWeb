import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { TicketChangelogEntry } from './TicketChangelogEntry';

describe('Component: TicketChangelogEntry', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ul>
        <TicketChangelogEntry
          entry={{
            type: 'resolved',
            resolution: 'fixed',
            user: createUser({ displayName: 'Scott' }),
            createdAt: '2024-05-02T00:00:00.000000Z',
          }}
          ticketableType="achievement"
        />
      </ul>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it.each([
    ['resolved', 'fixed', 'Scott resolved this ticket as fixed.', 'Resolved'],
    ['requested', null, 'Scott asked the reporter for more information.', 'Request'],
    ['returned', null, 'Scott moved this ticket from Request back to Open.', 'Open'],
    ['approved', null, 'Scott approved this ticket.', 'Open'],
    ['reopened', null, 'Scott reopened this ticket.', 'Open'],
    ['reminded', null, 'We reminded the reporter to reply.', 'Request'],
    [
      'closed',
      'no_reply_from_reporter',
      'We closed this ticket because the reporter did not reply. A reply from the reporter will reopen it.',
      'Closed',
    ],
    [
      'closed',
      'unable_to_reproduce',
      'Scott closed this ticket because they could not reproduce the problem.',
      'Closed',
    ],
    ['closed', 'demoted', 'Scott closed this ticket and demoted the achievement.', 'Closed'],
    ['closed', null, 'Scott closed this ticket.', 'Closed'],
  ] as const)(
    'given a %s entry with a %s resolution, describes it with the matching state glyph',
    (type, resolution, expectedSentence, expectedGlyphLabel) => {
      // ARRANGE
      render(
        <ul>
          <TicketChangelogEntry
            entry={{
              type,
              resolution,
              user: createUser({ displayName: 'Scott' }),
              createdAt: '2024-05-02T00:00:00.000000Z',
            }}
            ticketableType="achievement"
          />
        </ul>,
      );

      // ASSERT
      expect(screen.getByRole('listitem')).toHaveTextContent(expectedSentence);
      expect(screen.getByRole('img', { name: expectedGlyphLabel })).toBeVisible();
    },
  );

  it('given a leaderboard ticket closed by demotion, displays a message saying a leaderboard was demoted rahter than an achievement', () => {
    // ARRANGE
    render(
      <ul>
        <TicketChangelogEntry
          entry={{
            type: 'closed',
            resolution: 'demoted',
            user: createUser({ displayName: 'Scott' }),
            createdAt: '2024-05-02T00:00:00.000000Z',
          }}
          ticketableType="leaderboard"
        />
      </ul>,
    );

    // ASSERT
    expect(screen.getByRole('listitem')).toHaveTextContent(
      'Scott closed this ticket and demoted the leaderboard.',
    );
  });

  it.each([
    ['returned', null, 'achievement', 'This ticket was moved from Request back to Open.'],
    [
      'closed',
      'wrong_rom',
      'achievement',
      'This ticket was closed because the reporter used the wrong ROM.',
    ],
    ['closed', 'demoted', 'leaderboard', 'This ticket was closed and the leaderboard was demoted.'],
    ['closed', null, 'achievement', 'This ticket was closed.'],
  ] as const)(
    'given a %s entry with a %s resolution on an %s ticket and no known user, does not render anything about the user and does not crash',
    (type, resolution, ticketableType, expectedSentence) => {
      // ARRANGE
      render(
        <ul>
          <TicketChangelogEntry
            entry={{ type, resolution, user: null, createdAt: '2024-05-02T00:00:00.000000Z' }}
            ticketableType={ticketableType}
          />
        </ul>,
      );

      // ASSERT
      expect(screen.getByRole('listitem')).toHaveTextContent(expectedSentence);
      expect(screen.queryByText(/deleted user/i)).not.toBeInTheDocument();
    },
  );

  it('given the comment footer variant, describes the change without labeling the username', () => {
    // ARRANGE
    render(
      <TicketChangelogEntry
        entry={{
          type: 'resolved',
          resolution: 'fixed',
          user: createUser({ displayName: 'Gudra' }),
          createdAt: '2024-05-02T00:00:00.000000Z',
        }}
        ticketableType="achievement"
        variant="commentFooter"
      />,
    );

    // ASSERT
    expect(screen.getByText(/^resolved this ticket as fixed\./i)).toBeVisible();
    expect(screen.queryByText('Gudra')).not.toBeInTheDocument();
  });
});
