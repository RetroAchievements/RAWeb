import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { EventResultDisplay } from './EventResultDisplay';

describe('Component: EventResultDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({ state: 'active' });

    const { container } = render(<EventResultDisplay event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the event badge with correct attributes', () => {
    // ARRANGE
    const legacyGame = createGame({
      title: 'Achievement of the Week 2025',
      badgeUrl: 'https://example.com/aotw-2025.png',
    });

    const event = createRaEvent({ legacyGame, state: 'active' });

    render(<EventResultDisplay event={event} />);

    // ASSERT
    const badge = screen.getByAltText(/achievement of the week/i);

    expect(badge).toBeVisible();
    expect(badge).toHaveAttribute('src', 'https://example.com/aotw-2025.png');
    expect(badge).toHaveAttribute('alt', 'Achievement of the Week 2025');
  });

  it('displays the game title', () => {
    // ARRANGE
    const legacyGame = createGame({ title: 'Summer Event 2025' });
    const event = createRaEvent({ legacyGame, state: 'active' });

    render(<EventResultDisplay event={event} />);

    // ASSERT
    expect(screen.getByText(/summer event 2025/i)).toBeVisible();
  });

  it('given an active event, displays the active state label', () => {
    // ARRANGE
    const event = createRaEvent({ state: 'active' });

    render(<EventResultDisplay event={event} />);

    // ASSERT
    expect(screen.getByText(/active/i)).toBeVisible();
  });

  it('given an evergreen event, displays the no time limit state label', () => {
    // ARRANGE
    const event = createRaEvent({ state: 'evergreen' });

    render(<EventResultDisplay event={event} />);

    // ASSERT
    expect(screen.getByText(/no time limit/i)).toBeVisible();
  });

  it('given a concluded event, displays the concluded state label', () => {
    // ARRANGE
    const event = createRaEvent({ state: 'concluded' });

    render(<EventResultDisplay event={event} />);

    // ASSERT
    expect(screen.getByText(/concluded/i)).toBeVisible();
  });
});
