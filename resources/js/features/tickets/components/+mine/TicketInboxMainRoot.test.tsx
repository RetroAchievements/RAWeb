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

  it('given nothing needs attention, says so and only renders the reporter action section', () => {
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
    expect(screen.getByText('Nothing needs your attention right now.')).toBeVisible();
    expect(screen.getAllByRole('heading', { level: 2 }).map((h) => h.textContent)).toEqual([
      'Waiting on your feedback',
    ]);
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

  it('given a section with rows, renders its heading and links View all past the limit', () => {
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
    expect(screen.getByRole('link', { name: 'View all' })).toBeVisible();
  });
});
