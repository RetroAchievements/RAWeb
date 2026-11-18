import { createAuthenticatedUser, createAuthenticatedUserPreferences } from '@/common/models';
import { render, screen } from '@/test';
import { createTicketListEntry, createUser } from '@/test/factories';

import { TicketReport } from './TicketReport';

describe('Component: TicketReport', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketReport reportBody="It never unlocks." ticket={createTicketListEntry()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('shows the report body and the reporter', () => {
    // ARRANGE
    render(
      <TicketReport
        reportBody="It never unlocks."
        ticket={createTicketListEntry({ reporter: createUser({ displayName: 'PixelHero' }) })}
      />,
    );

    // ASSERT
    const report = screen.getByRole('article', { name: 'Report' });
    expect(report).toHaveTextContent('It never unlocks.');
    expect(report).toHaveTextContent('PixelHero');
  });

  it('given the user prefers absolute dates, shows the absolute report date', () => {
    // ARRANGE
    render(
      <TicketReport
        reportBody="It never unlocks."
        ticket={createTicketListEntry({ createdAt: '2024-05-01T14:30:00Z' })}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              preferences: createAuthenticatedUserPreferences({
                prefersAbsoluteDates: true,
                shouldAlwaysBypassContentWarnings: false,
              }),
            }),
          },
        },
      },
    );

    // ASSERT
    expect(screen.getByText('May 01, 2024, 14:30')).toBeVisible();
  });

  it('given the reporter account is gone, labels the reporter as a deleted user', () => {
    // ARRANGE
    render(
      <TicketReport
        reportBody="It never unlocks."
        ticket={createTicketListEntry({ reporter: null })}
      />,
    );

    // ASSERT
    expect(screen.getByText('Deleted user')).toBeVisible();
  });
});
