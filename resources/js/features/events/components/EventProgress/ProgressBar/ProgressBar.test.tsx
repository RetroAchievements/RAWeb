import { render, screen } from '@/test';

import { ProgressBar } from './ProgressBar';

describe('Component: ProgressBar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ProgressBar totalAchievementsCount={10} numEarnedAchievements={5} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no achievements, renders an empty progress bar', () => {
    // ARRANGE
    render(<ProgressBar totalAchievementsCount={0} numEarnedAchievements={0} />);

    // ASSERT
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toBeVisible();
    expect(progressBar).toHaveAttribute('aria-valuenow', '0');
  });

  it('given the user has earned some achievements, shows the correct percentage', () => {
    // ARRANGE
    render(<ProgressBar totalAchievementsCount={10} numEarnedAchievements={5} />);

    // ASSERT
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toHaveAttribute('aria-valuenow', '50');
    expect(screen.getByText(/50%/i)).toBeVisible();
  });

  it('given progress is below 1%, does not show the percentage label', () => {
    // ARRANGE
    render(<ProgressBar totalAchievementsCount={1000} numEarnedAchievements={1} />);

    // ASSERT
    expect(screen.queryByText(/%/i)).not.toBeInTheDocument();
  });

  it('given progress is 99% or higher, does not show the percentage label', () => {
    // ARRANGE
    render(<ProgressBar totalAchievementsCount={100} numEarnedAchievements={99} />);

    // ASSERT
    expect(screen.queryByText(/%/i)).not.toBeInTheDocument();
  });

  it('given the user has earned all achievements, shows a fully filled progress bar', () => {
    // ARRANGE
    render(<ProgressBar totalAchievementsCount={10} numEarnedAchievements={10} />);

    // ASSERT
    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toHaveAttribute('aria-valuenow', '100');
    expect(progressBar.querySelector('div')).toHaveStyle({ width: '100%' });
  });
});
