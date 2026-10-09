import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createTicketListEntry, createUser } from '@/test/factories';

import { TicketOutcomeFact } from './TicketOutcomeFact';

describe('Component: TicketOutcomeFact', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'resolved',
          resolution: 'fixed',
          createdAt: '2024-05-01T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the ticket was fixed, names the resolver and gives the duration with no date', () => {
    // ARRANGE
    const { container } = render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'resolved',
          resolution: 'fixed',
          createdAt: '2024-05-01T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      /^Scott resolved this ticket as fixed 2 days after the report\.$/,
    );
  });

  it('given the user hovers the duration, reveals the full resolved date', async () => {
    // ARRANGE
    render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'resolved',
          resolution: 'fixed',
          createdAt: '2024-05-01T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByText('2 days'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('May 3, 2024 12:00 PM');
  });

  it.each<[App.Community.Enums.TicketResolution, string]>([
    ['other', 'Scott closed this ticket 2 days after the report. See the comments for a reason.'],
    [
      'mistaken_report',
      'Scott closed this ticket 2 days after the report because it was a mistaken report.',
    ],
    [
      'not_enough_information',
      'Scott closed this ticket 2 days after the report because the report did not have enough information.',
    ],
    [
      'wrong_rom',
      'Scott closed this ticket 2 days after the report because the reporter used the wrong ROM.',
    ],
    [
      'network_problems',
      'Scott closed this ticket 2 days after the report because of network problems.',
    ],
    [
      'unable_to_reproduce',
      'Scott closed this ticket 2 days after the report because they could not reproduce the problem.',
    ],
    [
      'unable_to_debug',
      'Scott closed this ticket 2 days after the report because they could not debug the problem.',
    ],
  ])('given the %s resolution, prints "%s"', (resolution, expectedText) => {
    // ARRANGE
    const { container } = render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'closed',
          resolution,
          ticketableType: 'achievement',
          createdAt: '2024-05-01T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(expectedText);
  });

  it('given a closed ticket with a resolver but no resolution, names whoever closed it', () => {
    // ARRANGE
    const { container } = render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'closed',
          resolution: null,
          resolver: createUser({ displayName: 'Scott' }),
          createdAt: '2024-05-01T12:00:00Z',
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(/^Scott closed this ticket 2 days after the report\.$/);
  });

  it.each([
    ['2024-01-01T00:00:30Z', '1 minute'],
    ['2024-01-01T00:05:00Z', '5 minutes'],
    ['2024-01-01T00:59:59Z', '59 minutes'],
    ['2024-01-01T01:00:00Z', '1 hour'],
    ['2024-01-01T01:32:00Z', '1 hour 32 minutes'],
    ['2024-01-01T23:59:00Z', '23 hours 59 minutes'],
    ['2024-01-02T00:00:00Z', '1 day'],
    ['2024-01-07T00:00:00Z', '6 days'],
    ['2024-01-08T00:00:00Z', '1 week'],
    ['2024-01-14T00:00:00Z', '1 week'],
    ['2024-01-15T00:00:00Z', '2 weeks'],
    ['2024-01-20T00:00:00Z', '2 weeks'],
    ['2024-01-31T00:00:00Z', '4 weeks'],
    ['2024-02-01T00:00:00Z', '1 month'],
    ['2024-12-31T00:00:00Z', '11 months'],
    ['2025-01-01T00:00:00Z', '1 year'],
    ['2026-12-08T00:00:00Z', '2 years'],
  ])(
    'given the ticket was finished at %s, reports a duration of %s',
    (finishedAt, expectedSpan) => {
      // ARRANGE
      const { container } = render(
        <TicketOutcomeFact
          ticket={createTicketListEntry({
            resolvedAt: finishedAt,
            state: 'resolved',
            resolution: 'fixed',
            createdAt: '2024-01-01T00:00:00Z',
            resolver: createUser({ displayName: 'Scott' }),
          })}
        />,
      );

      // ASSERT
      expect(container).toHaveTextContent(`as fixed ${expectedSpan} after the report.`);
    },
  );

  it('given an achievement ticket tghat is closed as demoted, states that the closer demoted the achievement', () => {
    // ARRANGE
    const { container } = render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'closed',
          resolution: 'demoted',
          ticketableType: 'achievement',
          createdAt: '2024-05-01T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott closed this ticket and demoted the achievement 2 days after the report.',
    );
  });

  it('given a leaderboard ticket that is closed as demoted, states that the closer demoted the leaderboard', () => {
    // ARRANGE
    const { container } = render(
      <TicketOutcomeFact
        ticket={createTicketListEntry({
          resolvedAt: '2024-05-03T12:00:00Z',
          state: 'closed',
          resolution: 'demoted',
          ticketableType: 'leaderboard',
          createdAt: '2024-05-01T12:00:00Z',
          resolver: createUser({ displayName: 'Scott' }),
        })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'Scott closed this ticket and demoted the leaderboard 2 days after the report.',
    );
  });
});
