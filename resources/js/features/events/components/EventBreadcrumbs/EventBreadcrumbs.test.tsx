import { render, screen } from '@/test';
import { createGame, createRaEvent } from '@/test/factories';

import { EventBreadcrumbs } from './EventBreadcrumbs';

describe('Component: EventBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EventBreadcrumbs event={createRaEvent()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the All Events list', () => {
    // ARRANGE
    render(<EventBreadcrumbs event={createRaEvent()} />);

    // ASSERT
    const allEventsLinkEl = screen.getByRole('link', { name: /all events/i });
    expect(allEventsLinkEl).toBeVisible();
  });

  it('given an event, renders the title label', () => {
    // ARRANGE
    render(
      <EventBreadcrumbs
        event={createRaEvent({ legacyGame: createGame({ title: 'Achievement of the Week 2025' }) })}
      />,
    );

    // ASSERT
    expect(screen.getByText(/achievement of the week 2025/i)).toBeVisible();
  });
});
