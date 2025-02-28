import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { render, screen } from '@/test';
import { createRaEvent } from '@/test/factories';

import { StartDateChip } from './StartDateChip';

dayjs.extend(utc);

describe('Component: StartDateChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const { container } = render(<StartDateChip event={event} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the event has no start date, renders nothing', () => {
    // ARRANGE
    const event = createRaEvent({ activeFrom: null });
    render(<StartDateChip event={event} />);

    // ASSERT
    expect(screen.queryByText(/start/i)).not.toBeInTheDocument();
  });

  it('given the event has not started yet, shows when it will start', () => {
    // ARRANGE
    const futureDate = dayjs.utc().add(1, 'day').toISOString();
    const event = createRaEvent({ activeFrom: futureDate });

    render(<StartDateChip event={event} />);

    // ASSERT
    expect(screen.getByText(/starts/i)).toBeVisible();
  });

  it('given the event has already started, shows when it started', () => {
    // ARRANGE
    const pastDate = dayjs.utc().subtract(1, 'day').toISOString();
    const event = createRaEvent({ activeFrom: pastDate });

    render(<StartDateChip event={event} />);

    // ASSERT
    expect(screen.getByText(/started/i)).toBeVisible();
  });
});
