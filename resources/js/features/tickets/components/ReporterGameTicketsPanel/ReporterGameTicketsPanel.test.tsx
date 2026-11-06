import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createTicketListEntry } from '@/test/factories';

import { ReporterGameTicketsPanel } from './ReporterGameTicketsPanel';

describe('Component: ReporterGameTicketsPanel', () => {
  it('given the reporter opened other tickets for the same game, displays every one of them', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<ReporterGameTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry(),
        reporterGameTickets: [
          {
            id: 51,
            state: 'open',
            resolution: null,
            createdAt: '2024-05-01T12:00:00Z',
            ticketableTitle: 'Hookshot',
          },
          {
            id: 52,
            state: 'resolved',
            resolution: 'fixed',
            createdAt: '2024-05-02T12:00:00Z',
            ticketableTitle: 'Zora Tunic',
          },
        ],
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: 'From the Same Reporter' })).toBeVisible();
    expect(screen.getByRole('link', { name: '#51' })).toBeVisible();
    expect(screen.getByRole('link', { name: '#52' })).toBeVisible();
    expect(screen.getByText('Hookshot')).toBeVisible();
    expect(screen.getByText('Zora Tunic')).toBeVisible();
  });

  it('shows how far before or after this ticket each one was created', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<ReporterGameTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ createdAt: '2024-05-10T12:00:00Z' }),
        reporterGameTickets: [
          {
            id: 51,
            state: 'open',
            resolution: null,
            createdAt: '2024-05-10T11:40:00Z',
            ticketableTitle: 'Hookshot',
          },
          {
            id: 52,
            state: 'open',
            resolution: null,
            createdAt: '2024-05-13T12:00:00Z',
            ticketableTitle: 'Zora Tunic',
          },
        ],
      },
    });

    // ASSERT
    expect(screen.getByRole('cell', { name: 'Opened' })).toBeVisible();
    expect(screen.getByRole('row', { name: /#51 Hookshot 20m earlier/ })).toBeVisible();
    expect(screen.getByRole('row', { name: /#52 Zora Tunic 3d later/ })).toBeVisible();
  });

  it('given more than five tickets, can be expanded and retracted', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<ReporterGameTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry(),
        reporterGameTickets: [1, 2, 3, 4, 5, 6, 7].map((id) => ({
          id,
          state: 'open' as const,
          resolution: null,
          createdAt: '2024-05-01T12:00:00Z',
          ticketableTitle: `Challenge ${id}`,
        })),
      },
    });

    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(5);
    expect(screen.queryByRole('link', { name: '#6' })).not.toBeInTheDocument();

    await userEvent.click(screen.getByRole('button', { name: 'See 2 more' }));

    // ASSERT
    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(7);
    expect(screen.getByRole('button', { name: 'See 2 more' })).toHaveAttribute(
      'aria-expanded',
      'true',
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'See 2 more' }));

    // ASSERT
    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(5);
  });

  it('given the browser has measured the table, keeps the extra tickets in view until the table finishes shrinking', async () => {
    // ARRANGE
    const originalResizeObserver = window.ResizeObserver;
    window.ResizeObserver = class MockResizeObserver {
      private callback: ResizeObserverCallback;
      constructor(callback: ResizeObserverCallback) {
        this.callback = callback;
      }
      observe() {
        this.callback([], this as unknown as ResizeObserver);
      }
      unobserve() {}
      disconnect() {}
    } as unknown as typeof ResizeObserver;
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(300);

    render<App.Platform.Data.TicketShowPageProps>(<ReporterGameTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry(),
        reporterGameTickets: [1, 2, 3, 4, 5, 6, 7].map((id) => ({
          id,
          state: 'open' as const,
          resolution: null,
          createdAt: '2024-05-01T12:00:00Z',
          ticketableTitle: `Challenge ${id}`,
        })),
      },
    });
    await userEvent.click(screen.getByRole('button', { name: 'See 2 more' }));

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'See 2 more' }));

    // ASSERT
    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(7);
    await waitFor(() => {
      expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(5);
    });

    window.ResizeObserver = originalResizeObserver;
  });
});
