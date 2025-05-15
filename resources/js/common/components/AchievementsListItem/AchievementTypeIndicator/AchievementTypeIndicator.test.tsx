import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { AchievementTypeIndicator } from './AchievementTypeIndicator';

describe('Component: AchievementTypeIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementTypeIndicator type="missable" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the type is missable, renders the missable icon with correct label', () => {
    // ARRANGE
    render(<AchievementTypeIndicator type="missable" />);

    // ASSERT
    expect(screen.getByLabelText(/missable/i)).toBeVisible();
  });

  it('given the type is progression, renders the progression icon with correct label', () => {
    // ARRANGE
    render(<AchievementTypeIndicator type="progression" />);

    // ASSERT
    expect(screen.getByLabelText(/progression/i)).toBeVisible();
  });

  it('given the type is win_condition, renders the win condition icon with correct label', () => {
    // ARRANGE
    render(<AchievementTypeIndicator type="win_condition" />);

    // ASSERT
    expect(screen.getByLabelText(/win condition/i)).toBeVisible();
  });

  it('renders a tooltip with the appropriate label when hovered', async () => {
    // ARRANGE
    render(<AchievementTypeIndicator type="missable" />);

    // ACT
    await userEvent.hover(screen.getByLabelText(/missable/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('tooltip', { name: /missable/i })).toBeVisible();
    });
  });
});
