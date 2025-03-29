import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createEventAchievement, createRaEvent } from '@/test/factories';

import { EventAchievementSets } from './EventAchievementSets';

describe('Component: EventAchievementSets', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<EventAchievementSets event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no achievements, renders an empty state', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: undefined,
    });

    render(<EventAchievementSets event={event} />);

    // ASSERT
    expect(screen.queryByTestId('event-achievement-sets')).not.toBeInTheDocument();
    expect(screen.getByText(/there aren't any achievements for this event/i)).toBeVisible();
  });

  it('given the event is evergreen, defaults to display order sort and hides active option', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'evergreen',
      eventAchievements: [
        createEventAchievement({
          achievement: createAchievement({
            id: 1,
            title: 'Test Achievement',
          }),
        }),
      ],
    });

    render(<EventAchievementSets event={event} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /display order/i })).toBeVisible();
    expect(screen.queryByText(/active/i)).not.toBeInTheDocument();
  });

  it('given the event is not evergreen, defaults to status sort', () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'active',
      eventAchievements: [
        createEventAchievement({
          achievement: createAchievement({
            id: 1,
            title: 'Test Achievement',
          }),
        }),
      ],
    });

    render(<EventAchievementSets event={event} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /status/i })).toBeVisible();
  });

  it('given the user changes the sort order, updates the sort', async () => {
    // ARRANGE
    const event = createRaEvent({
      state: 'active',
      eventAchievements: [
        createEventAchievement({
          achievement: createAchievement({
            id: 1,
            title: 'Test Achievement',
          }),
        }),
      ],
    });

    render(<EventAchievementSets event={event} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /status/i }));
    await userEvent.click(screen.getByText(/display order \(first\)/i));

    // ASSERT
    // ... the button should now show the new sort option ...
    expect(screen.getByRole('button', { name: /display order/i })).toBeVisible();
  });

  it('given some event achievements have no achievement data, does not crash', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        {
          achievement: undefined,
          isObfuscated: false,
        },
        {
          achievement: createAchievement({
            id: 1,
            title: 'Valid Achievement',
          }),
          isObfuscated: false,
        },
      ],
    });

    const { container } = render(<EventAchievementSets event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByText(/valid achievement/i)).toBeVisible();
  });
});
