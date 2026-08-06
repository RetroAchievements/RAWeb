import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createActiveEventAchievement } from '@/test/factories';

import { ActiveEventsIndicator } from './ActiveEventsIndicator';

describe('Component: ActiveEventsIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const activeEvents = createActiveEventAchievement();
    const { container } = render(<ActiveEventsIndicator activeEvents={activeEvents} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a tooltip with the appropriate label when hovered', async () => {
    // ARRANGE
    const activeEvents = createActiveEventAchievement({summary: 'Event 1 (Evergreen)'});
    const { container } = render(<ActiveEventsIndicator activeEvents={activeEvents} />);

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('tooltip', { name: /event 1/i })).toBeVisible();
    });
  });
});
