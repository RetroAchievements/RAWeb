import { render, screen } from '@/test';
import { createTicketInboxSection, createTicketListEntry } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { TicketInboxSection } from './TicketInboxSection';

function renderSection(
  props: Partial<React.ComponentProps<typeof TicketInboxSection>> = {},
  sectionLimit = 8,
) {
  return render<App.Platform.Data.TicketInboxPageProps>(
    <TicketInboxSection
      counterpartyColumnId="reporter"
      section={createTicketInboxSection()}
      t_heading={'Waiting on you' as TranslatedString}
      {...props}
    />,
    { pageProps: { sectionLimit, sections: [], attentionCount: 0 } },
  );
}

describe('Component: TicketInboxSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = renderSection({
      t_emptyMessage: 'Nothing here.' as TranslatedString,
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the section is empty and has empty copy, shows that copy and no associated count', () => {
    // ARRANGE
    renderSection({ t_emptyMessage: 'Nothing here.' as TranslatedString });

    // ASSERT
    expect(screen.getByText('Nothing here.')).toBeVisible();
    expect(screen.getByRole('heading', { level: 2, name: 'Waiting on you' })).toBeVisible();
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
    expect(screen.queryByText('0')).not.toBeInTheDocument();
  });

  it('given the section is empty and has no empty copy, renders nothing at all', () => {
    // ARRANGE
    renderSection();

    // ASSERT
    expect(screen.queryByRole('heading', { level: 2 })).not.toBeInTheDocument();
  });

  it('given the section has rows, shows the count and the rows', () => {
    // ARRANGE
    renderSection({
      section: createTicketInboxSection({
        count: 3,
        tickets: [
          createTicketListEntry({ id: 1001, ticketableTitle: 'First Blood' }),
          createTicketListEntry({ id: 1002, ticketableTitle: 'Second Wind' }),
        ],
      }),
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: /Waiting on you/ })).toHaveTextContent(
      '3',
    );
    expect(screen.getAllByText('First Blood').length).toBeGreaterThan(0);
    expect(screen.getAllByText('Second Wind').length).toBeGreaterThan(0);
  });

  it('given the count is above the section hard limit, shows a View all link', () => {
    // ARRANGE
    renderSection({
      section: createTicketInboxSection({ count: 12, tickets: [createTicketListEntry()] }),
      viewAllHref: '/user/Scott/tickets2',
    });

    // ASSERT
    expect(screen.getByRole('link', { name: 'View all' })).toHaveAttribute(
      'href',
      '/user/Scott/tickets2',
    );
  });

  it('given the count is within the section hard limit, shows no View all link', () => {
    // ARRANGE
    renderSection({
      section: createTicketInboxSection({ count: 2, tickets: [createTicketListEntry()] }),
      viewAllHref: '/user/Scott/tickets2',
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: 'View all' })).not.toBeInTheDocument();
  });

  it('given a counterparty column, shows that column', () => {
    // ARRANGE
    renderSection({
      counterpartyColumnId: 'developer',
      section: createTicketInboxSection({ count: 1, tickets: [createTicketListEntry()] }),
    });

    // ASSERT
    const headers = screen.getAllByRole('columnheader').map((header) => header.textContent);
    expect(headers).toEqual(['ID', 'Issue with', 'Game', 'Developer', 'Age']);
  });
});
