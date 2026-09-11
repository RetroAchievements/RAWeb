import { render, screen } from '@/test';

import { TicketPageLayout } from './TicketPageLayout';

describe('Component: TicketPageLayout', () => {
  it('shows the shared heading, page content, and view links', () => {
    // ARRANGE
    render(<TicketPageLayout currentView="mine">Page content</TicketPageLayout>);

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: 'Tickets' })).toBeVisible();
    expect(screen.getByText('Page content')).toBeVisible();
    expect(screen.getByRole('link', { name: 'For you' })).toHaveAttribute('href', 'tickets.mine');
    expect(screen.getByRole('link', { name: 'All tickets' })).toHaveAttribute(
      'href',
      'tickets.index',
    );
  });

  it('given the inbox view is active, marks For you as the current page', () => {
    // ARRANGE
    render(<TicketPageLayout currentView="mine">Page content</TicketPageLayout>);

    // ASSERT
    expect(screen.getByRole('link', { name: 'For you' })).toHaveAttribute('aria-current', 'page');
    expect(screen.getByRole('link', { name: 'All tickets' })).not.toHaveAttribute('aria-current');
  });

  it('given the index view is set, marks All tickets as the current page', () => {
    // ARRANGE
    render(<TicketPageLayout currentView="all">Page content</TicketPageLayout>);

    // ASSERT
    expect(screen.getByRole('link', { name: 'All tickets' })).toHaveAttribute(
      'aria-current',
      'page',
    );
    expect(screen.getByRole('link', { name: 'For you' })).not.toHaveAttribute('aria-current');
  });
});
