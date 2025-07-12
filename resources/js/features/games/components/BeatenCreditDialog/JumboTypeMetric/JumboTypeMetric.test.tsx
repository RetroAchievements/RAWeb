import { render, screen } from '@/test';

import { JumboTypeMetric } from './JumboTypeMetric';

describe('Component: JumboTypeMetric', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<JumboTypeMetric current={5} total={10} type="progression" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the type is progression, displays the progression label', () => {
    // ARRANGE
    render(<JumboTypeMetric current={5} total={10} type="progression" />);

    // ASSERT
    expect(screen.getByText(/progression/i)).toBeVisible();
  });

  it('given the type is win_condition, displays the win condition label', () => {
    // ARRANGE
    render(<JumboTypeMetric current={3} total={8} type="win_condition" />);

    // ASSERT
    expect(screen.getByText(/win condition/i)).toBeVisible();
  });

  it('given current and total values, displays them in the correct format', () => {
    // ARRANGE
    render(<JumboTypeMetric current={7} total={15} type="progression" />);

    // ASSERT
    expect(screen.getByText(/7\/15/)).toBeVisible();
  });

  it('given the type is progression, renders the progress bar with green color', () => {
    // ARRANGE
    render(<JumboTypeMetric current={5} total={10} type="progression" />);

    // ASSERT
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toBeVisible();

    const progressSegment = progressBar.querySelector('.bg-green-600');
    expect(progressSegment).toBeVisible();
  });

  it('given the type is win_condition, renders the progress bar with amber color', () => {
    // ARRANGE
    render(<JumboTypeMetric current={3} total={8} type="win_condition" />);

    // ASSERT
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toBeVisible();

    const progressSegment = progressBar.querySelector('.bg-amber-600');
    expect(progressSegment).toBeVisible();
  });

  it('given the current equals total, still displays the values correctly', () => {
    // ARRANGE
    render(<JumboTypeMetric current={10} total={10} type="progression" />);

    // ASSERT
    expect(screen.getByText(/10\/10/)).toBeVisible();
  });

  it('given zero progress, still displays the values correctly', () => {
    // ARRANGE
    render(<JumboTypeMetric current={0} total={50} type="win_condition" />);

    // ASSERT
    expect(screen.getByText(/0\/50/)).toBeVisible();
  });

  it('sets the correct max value on the progress bar', () => {
    // ARRANGE
    const total = 25;
    render(<JumboTypeMetric current={10} total={total} type="progression" />);

    // ASSERT
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toHaveAttribute('data-max', total.toString());
  });
});
