import { render, screen } from '@/test';
import { createTicketInboxSection, createTicketListEntry, createUser } from '@/test/factories';

import { TicketInboxMainRoot } from './TicketInboxMainRoot';

function buildEmptySections(): App.Platform.Data.TicketInboxSection[] {
  return [
    createTicketInboxSection({ kind: 'toResolve' }),
    createTicketInboxSection({ kind: 'awaitingYourFeedback' }),
    createTicketInboxSection({ kind: 'awaitingReporter' }),
    createTicketInboxSection({ kind: 'reportedOpen' }),
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
});
