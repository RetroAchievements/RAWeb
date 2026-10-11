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

  it('shows the heading, the reporter, and the absolute report date', () => {
    // ARRANGE
    render(
      <TicketReport
        reportBody="It never unlocks."
        ticket={createTicketListEntry({
          createdAt: '2024-05-01T14:30:00Z',
          reporter: createUser({ displayName: 'PixelHero' }),
        })}
      />,
    );

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: 'Report' })).toBeVisible();
    expect(screen.getByRole('link', { name: /pixelhero/i })).toBeVisible();
    expect(screen.getByText('May 1, 2024 2:30 PM')).toBeVisible();
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
