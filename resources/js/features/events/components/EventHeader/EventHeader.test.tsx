import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { EventHeader } from './EventHeader';

describe('Component: EventHeader', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<EventHeader event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no legacy game, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: undefined });
    render(<EventHeader event={event} />);

    // ASSERT
    expect(screen.queryByTestId('header-content')).not.toBeInTheDocument();
  });

  it('given the event has a legacy game, renders all required elements', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame({
        badgeUrl: '/test-badge.png',
        title: 'Test Game Title',
      }),
    });

    render(<EventHeader event={event} />);

    // ASSERT
    expect(screen.getAllByRole('img')[0]).toHaveAttribute('src', '/test-badge.png');
    expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent('Test Game Title');
    expect(screen.getByText(/events/i)).toBeVisible();
  });
});
