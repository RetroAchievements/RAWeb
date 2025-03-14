import { render, screen } from '@/test';
import { createEventAchievement, createRaEvent } from '@/test/factories';

import { IsPlayableChip } from './IsPlayableChip';

describe('Component: IsPlayableChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<IsPlayableChip event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no achievements, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({ eventAchievements: undefined });
    render(<IsPlayableChip event={event} />);

    // ASSERT
    expect(screen.queryByTestId('playable')).not.toBeInTheDocument();
  });

  it('given the event is active, shows the active state', () => {
    // ARRANGE
    const event = createRaEvent({ state: 'active', eventAchievements: [createEventAchievement()] });
    render(<IsPlayableChip event={event} />);

    // ASSERT
    expect(screen.getByText(/active/i)).toBeVisible();
  });

  it('given the event is evergreen, shows the no time limit state with tooltip', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'evergreen',
      eventAchievements: [createEventAchievement()],
    });
    render(<IsPlayableChip event={event} />);

    // ASSERT
    expect(screen.getByText(/no time limit/i)).toBeVisible();
  });

  it('given the event has concluded, shows the concluded state', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'concluded',
      eventAchievements: [createEventAchievement()],
    });
    render(<IsPlayableChip event={event} />);

    // ASSERT
    expect(screen.getByText(/concluded/i)).toBeVisible();
  });
});
