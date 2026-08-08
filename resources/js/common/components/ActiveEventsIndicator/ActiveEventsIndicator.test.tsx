import userEvent from '@testing-library/user-event';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';
import { createActiveEventAchievement } from '@/test/factories';

import { ActiveEventsIndicator } from './ActiveEventsIndicator';

dayjs.extend(utc);

describe('Component: ActiveEventsIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ActiveEventsIndicator activeEvents={[createActiveEventAchievement()]} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });
  it('given there are no active events, renders nothing', () => {
    // ARRANGE
    render(<ActiveEventsIndicator activeEvents={[]} />);

    // ASSERT
    expect(screen.queryByTestId('type-active_event')).not.toBeInTheDocument();
  });

  it('given the event ends in several days, counts down in days', async () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({
            eventTitle: 'Halloween Bash',
            activeUntil: dayjs.utc('2023-10-28').toISOString(),
          }),
        ]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    const tooltipEl = await screen.findByRole('tooltip');
    expect(tooltipEl).toHaveTextContent('Halloween Bash');
    expect(tooltipEl).toHaveTextContent('3 days left');
  });

  it('given the event ends tomorrow, counts down with a singular day', async () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({
            activeUntil: dayjs.utc('2023-10-26').toISOString(),
          }),
        ]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('1 day left');
  });

  it('given the event ends within the day, counts down in hours', async () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25T00:00:00Z').toDate());

    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({
            activeUntil: dayjs.utc('2023-10-25T05:00:00Z').toISOString(),
          }),
        ]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('5 hours left');
  });

  it('given the event ends within the hour, says it is the last day', async () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25T00:00:00Z').toDate());

    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({
            activeUntil: dayjs.utc('2023-10-25T00:30:00Z').toISOString(),
          }),
        ]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('Last day');
  });

  it('given the event ends more than two months out, counts down in months', async () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({
            activeUntil: dayjs.utc('2024-01-25').toISOString(),
          }),
        ]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('3 months left');
  });

  it('given the achievement belongs to multiple events, lists all of them', async () => {
    // ARRANGE
    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({ eventId: 1, eventTitle: 'Event One' }),
          createActiveEventAchievement({ eventId: 2, eventTitle: 'Event Two' }),
        ]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByTestId('type-active_event'));

    // ASSERT
    const tooltipEl = await screen.findByRole('tooltip');
    expect(tooltipEl).toHaveTextContent('Event One');
    expect(tooltipEl).toHaveTextContent('Event Two');
  });

  it('given there is a single event, links directly to that event', () => {
    // ARRANGE
    render(
      <ActiveEventsIndicator
        activeEvents={[createActiveEventAchievement({ eventId: 17, achievementId: 55 })]}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link')).toHaveAttribute('href', 'event.show,17');
  });

  it('given there are multiple events, links to the achievement instead', () => {
    // ARRANGE
    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({ eventId: 17, achievementId: 55 }),
          createActiveEventAchievement({ eventId: 18, achievementId: 55 }),
        ]}
      />,
    );

    // ASSERT
    expect(screen.getByRole('link')).toHaveAttribute('href', 'achievement.show,55');
  });

  it('given the user unlocked every event achievement, highlights the indicator', () => {
    // ARRANGE
    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({ userUnlocked: true }),
          createActiveEventAchievement({ userUnlocked: true }),
        ]}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('type-active_event')).toHaveClass('border-amber-400');
  });

  it('given the user is missing any event achievement, does not highlight the indicator', () => {
    // ARRANGE
    render(
      <ActiveEventsIndicator
        activeEvents={[
          createActiveEventAchievement({ userUnlocked: true }),
          createActiveEventAchievement({ userUnlocked: false }),
        ]}
      />,
    );

    // ASSERT
    expect(screen.getByTestId('type-active_event')).not.toHaveClass('border-amber-400');
  });
});
