import { render, screen } from '@/test';

import { BeatenCreditAlert } from './BeatenCreditAlert';

describe('Component: BeatenCreditAlert', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BeatenCreditAlert hasProgressionAchievements={true} hasWinConditionAchievements={true} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('always shows the alert title', () => {
    // ARRANGE
    render(
      <BeatenCreditAlert hasProgressionAchievements={true} hasWinConditionAchievements={true} />,
    );

    // ASSERT
    expect(screen.getByText(/how to earn beaten credit:/i)).toBeVisible();
  });

  it('given both progression and win condition achievements, shows the combined message', () => {
    // ARRANGE
    render(
      <BeatenCreditAlert hasProgressionAchievements={true} hasWinConditionAchievements={true} />,
    );

    // ASSERT
    const alertDescription = screen.getByRole('alert').querySelector('[class*="relaxed"]');
    expect(alertDescription).toHaveTextContent(
      'Unlock ALL progression achievements and ANY win condition achievement.',
    );
    expect(screen.getByText('ALL')).toBeVisible();
    expect(screen.getByText('ANY')).toBeVisible();
  });

  it('given only progression achievements, shows the progression-only message', () => {
    // ARRANGE
    render(
      <BeatenCreditAlert hasProgressionAchievements={true} hasWinConditionAchievements={false} />,
    );

    // ASSERT
    const alertDescription = screen.getByRole('alert').querySelector('[class*="relaxed"]');
    expect(alertDescription).toHaveTextContent('Unlock ALL progression achievements.');
    expect(screen.getByText('ALL')).toBeVisible();
    expect(screen.queryByText('win condition achievement')).not.toBeInTheDocument();
  });

  it('given only win condition achievements, shows the win condition-only message', () => {
    // ARRANGE
    render(
      <BeatenCreditAlert hasProgressionAchievements={false} hasWinConditionAchievements={true} />,
    );

    // ASSERT
    const alertDescription = screen.getByRole('alert').querySelector('[class*="relaxed"]');
    expect(alertDescription).toHaveTextContent('Unlock ANY win condition achievement.');
    expect(screen.getByText('ANY')).toBeVisible();
    expect(screen.queryByText('progression achievements')).not.toBeInTheDocument();
  });
});
