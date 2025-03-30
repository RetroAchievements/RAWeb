import { persistedEventsAtom } from '@/common/state/shortcode.atoms';
import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { ShortcodeEvent } from './ShortcodeEvent';

describe('Component: ShortcodeEvent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ShortcodeEvent eventId={1} />, {
      jotaiAtoms: [
        [persistedEventsAtom, []],
        //
      ],
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event ID is not found in persisted events, renders nothing', () => {
    // ARRANGE
    render(<ShortcodeEvent eventId={1111111} />, {
      jotaiAtoms: [
        [persistedEventsAtom, [createRaEvent({ id: 1 })]],
        //
      ],
    });

    // ASSERT
    expect(screen.queryByTestId('event-embed')).not.toBeInTheDocument();
  });

  it('given the event ID is found in persisted events, renders the event avatar', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 1,
      legacyGame: createGame({ title: 'Achievement of the Week 2025' }),
    });

    render(<ShortcodeEvent eventId={1} />, {
      jotaiAtoms: [
        [persistedEventsAtom, [event]],
        //
      ],
    });

    // ASSERT
    expect(screen.getByTestId('event-embed')).toBeVisible();

    expect(screen.getByRole('img', { name: /achievement of the week/i })).toBeVisible();
    expect(screen.getByText(/achievement of the week/i)).toBeVisible();
    expect(screen.getByRole('link')).toBeVisible();
  });

  it('links to the correct page', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 1,
      legacyGame: createGame({ title: 'Achievement of the Week 2025' }),
    });

    render(<ShortcodeEvent eventId={1} />, {
      jotaiAtoms: [
        [persistedEventsAtom, [event]],
        //
      ],
    });

    // ASSERT
    const linkEl = screen.getByRole('link');
    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('game.show'));
  });
});
