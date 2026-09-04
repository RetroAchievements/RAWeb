import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { TicketStateGlyph } from './TicketStateGlyph';

describe('Component: TicketStateGlyph', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TicketStateGlyph state="open" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a ticket in the request state, labels the glyph and shows a tooltip on hover', async () => {
    // ARRANGE
    render(<TicketStateGlyph state="request" />);

    // ACT
    await userEvent.hover(screen.getByRole('img', { name: 'Request' }));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('Request');
  });

  it('given a ticket in the quarantined state, the tooltip says quarantined', async () => {
    // ARRANGE
    render(<TicketStateGlyph state="quarantined" />);

    // ACT
    await userEvent.hover(screen.getByRole('img', { name: 'Quarantined' }));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('Quarantined');
  });

  it.each([
    ['open', 'Open'],
    ['resolved', 'Resolved'],
    ['closed', 'Closed'],
  ] as const)('given a ticket in the %s state, labels the glyph %s', (state, label) => {
    // ARRANGE
    render(<TicketStateGlyph state={state} />);

    // ASSERT
    expect(screen.getByRole('img', { name: label })).toBeVisible();
  });

  it.each(['open', 'request', 'resolved', 'closed', 'quarantined'] as const)(
    'given a ticket in the %s state, renders a glyph shaped for that state',
    (state) => {
      // ARRANGE
      render(<TicketStateGlyph state={state} />);

      // ASSERT
      expect(screen.getByRole('img')).toHaveAttribute('data-state', state);
    },
  );
});
