import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import { createTicketInboxSection, createTicketListEntry, createUser } from '@/test/factories';

import { TicketInboxMainRoot } from './TicketInboxMainRoot';

function buildEmptySections(): App.Platform.Data.TicketInboxSection[] {
  return [
    createTicketInboxSection({ kind: 'toResolve' }),
    createTicketInboxSection({ kind: 'awaitingYourFeedback' }),
    createTicketInboxSection({ kind: 'awaitingReporter' }),
    createTicketInboxSection({ kind: 'subscribedByYou' }),
    createTicketInboxSection({ kind: 'reportedByYou' }),
    createTicketInboxSection({ kind: 'resolvedByYou' }),
  ];
}

describe('Component: TicketInboxMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: buildEmptySections(),
        sectionLimit: 8,
        attentionCount: 0,
        user: createUser({ displayName: 'Scott' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given nothing needs attention, says so', () => {
    // ARRANGE
    render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: buildEmptySections(),
        sectionLimit: 8,
        attentionCount: 0,
        user: createUser({ displayName: 'Scott' }),
      },
    });

    // ASSERT
    expect(screen.getByText("You're all caught up.")).toBeVisible();
  });

  it('given one ticket needs attention, uses the singular copy', () => {
    // ARRANGE
    render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: buildEmptySections(),
        sectionLimit: 8,
        attentionCount: 1,
        user: createUser({ displayName: 'Scott' }),
      },
    });

    // ASSERT
    expect(screen.getByText('1 ticket needs your attention.')).toBeVisible();
  });

  it('given multiple tickets need attention, reports the whole total in plural form', () => {
    // ARRANGE
    render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: buildEmptySections(),
        sectionLimit: 8,
        attentionCount: 4,
        user: createUser({ displayName: 'Scott' }),
      },
    });

    // ASSERT
    expect(screen.getByText('4 tickets need your attention.')).toBeVisible();
  });

  it('given resolved tickets and no pending actions, keeps the history and links to resolved tickets', () => {
    // ARRANGE
    render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: [
          createTicketInboxSection({
            kind: 'resolvedByYou',
            count: 12,
            tickets: [createTicketListEntry({ id: 1001 })],
          }),
        ],
        sectionLimit: 8,
        attentionCount: 0,
        can: {},
        user: createUser({ displayName: 'Scott' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: /Resolved by you/ })).toHaveTextContent(
      '12',
    );
    expect(screen.getByText("You're all caught up.")).toBeVisible();
    expect(screen.getByRole('link', { name: 'View all' })).toBeVisible();
  });

  it('given reported history and no pending actions, shows the history and links to all statuses', () => {
    // ARRANGE
    render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: [
          createTicketInboxSection({
            kind: 'reportedByYou',
            count: 12,
            tickets: [createTicketListEntry({ id: 1001 })],
          }),
        ],
        sectionLimit: 8,
        attentionCount: 0,
        can: {},
        user: createUser({ displayName: 'Dugtrio' }),
      },
    });

    // ASSERT
    expect(route).toHaveBeenCalledWith('user.tickets.created', {
      user: 'Dugtrio',
      'filter[status]': 'all',
    });
    expect(screen.getByRole('heading', { level: 2, name: /Reported by you/ })).toHaveTextContent(
      '12',
    );
    expect(screen.getByText("You're all caught up.")).toBeVisible();
    expect(screen.getByRole('link', { name: 'View all' })).toHaveAttribute(
      'href',
      expect.stringContaining('user.tickets.created,'),
    );
  });

  it('given open tickets on games subscribed to by the user, shows and links to them', () => {
    // ARRANGE
    render<App.Platform.Data.TicketInboxPageProps>(<TicketInboxMainRoot />, {
      pageProps: {
        sections: [
          createTicketInboxSection({
            kind: 'subscribedByYou',
            count: 10,
            tickets: [createTicketListEntry({ id: 1001 })],
          }),
        ],
        sectionLimit: 8,
        attentionCount: 0,
        can: {},
        user: createUser({ displayName: 'Dugtrio' }),
      },
    });

    // ASSERT
    expect(route).toHaveBeenCalledWith('tickets.subscribed', { 'filter[status]': 'open' });
    expect(
      screen.getByRole('heading', { level: 2, name: /Subscribed to by you/ }),
    ).toHaveTextContent('10');
    expect(screen.getByRole('row', { name: /1001/ })).toBeVisible();
    expect(screen.getByRole('link', { name: 'View all' })).toHaveAttribute(
      'href',
      expect.stringContaining('tickets.subscribed,'),
    );
  });
});
