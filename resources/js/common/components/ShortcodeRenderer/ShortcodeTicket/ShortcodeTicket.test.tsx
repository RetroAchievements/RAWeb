import { render, screen } from '@/test';
import { createAchievement, createTicket } from '@/test/factories';

import { persistedTicketsAtom } from '../../../state/shortcode.atoms';
import { ShortcodeTicket } from './ShortcodeTicket';

describe('Component: ShortcodeTicket', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'open',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    const { container } = render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no matching ticket is found, renders nothing', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'open',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    // !! using id 999
    render(<ShortcodeTicket ticketId={999} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByTestId('achievement-ticket-embed')).not.toBeInTheDocument();
  });

  it('given an achievement ticket is found, renders it properly', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'open',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('ticket.show'));

    const imageEl = screen.getByRole('img');
    expect(imageEl).toHaveAttribute('src', 'test-badge.png');
    expect(screen.getByText(/ticket #123/i)).toBeVisible();
  });

  it('given a non-achievement ticket is found, renders nothing', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'leaderboard',
      state: 'open',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByTestId('achievement-ticket-embed')).not.toBeInTheDocument();
    expect(screen.queryByTestId('leaderboard-ticket-embed')).not.toBeInTheDocument();
  });

  it('given an open ticket, applies green border styling', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'open',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('link')).toHaveClass('border-green-600');
  });

  it('given a request ticket, applies green border styling', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'request',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('link')).toHaveClass('border-green-600');
  });

  it('given a closed ticket, applies red border styling', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'closed',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('link')).toHaveClass('border-red-600');
  });

  it('given a resolved ticket, applies red border styling', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: 'resolved',
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByRole('link')).toHaveClass('border-red-600');
  });

  it('given an undefined ticket state, applies no border styling', () => {
    // ARRANGE
    const ticket = createTicket({
      id: 123,
      ticketableType: 'achievement',
      state: undefined,
      ticketable: createAchievement({ badgeUnlockedUrl: 'test-badge.png' }),
    });

    render(<ShortcodeTicket ticketId={123} />, {
      jotaiAtoms: [
        [persistedTicketsAtom, [ticket]],
        //
      ],
    });

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).not.toHaveClass('border-green-600');
    expect(linkEl).not.toHaveClass('border-red-600');
  });
});
