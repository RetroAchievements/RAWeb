import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';
import { createRaEvent } from '@/test/factories';

import { EndDateChip } from './EndDateChip';

dayjs.extend(utc);

describe('Component: EndDateChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<EndDateChip event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no end date, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({ activeThrough: null });
    render(<EndDateChip event={event} />);

    // ASSERT
    expect(screen.queryByText(/end/i)).not.toBeInTheDocument();
  });

  it('given the event has not ended yet, shows when it will end', () => {
    // ARRANGE
    const futureDate = dayjs.utc().add(1, 'day').toISOString();
    const event = createRaEvent({ activeThrough: futureDate });

    render(<EndDateChip event={event} />);

    // ASSERT
    expect(screen.getByText(/ends/i)).toBeVisible();
  });

  it('given the event has already ended, shows when it ended', () => {
    // ARRANGE
    const pastDate = dayjs.utc().subtract(1, 'day').toISOString();
    const event = createRaEvent({ activeThrough: pastDate });

    render(<EndDateChip event={event} />);

    // ASSERT
    expect(screen.getByText(/ended/i)).toBeVisible();
  });
});
