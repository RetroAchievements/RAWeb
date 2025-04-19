import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { SelectableChip } from './SelectableChip';

describe('Component: SelectableChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SelectableChip isSelected={false} onClick={() => {}}>
        Test Chip
      </SelectableChip>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the chip is not selected, communicates that in an accessible way', () => {
    // ARRANGE
    render(
      <SelectableChip isSelected={false} onClick={() => {}}>
        Test Chip
      </SelectableChip>,
    );

    // ASSERT
    const chipButton = screen.getByRole('button', { name: /test chip/i });

    expect(chipButton).toBeVisible();
    expect(chipButton).toHaveAttribute('aria-pressed', 'false');
  });

  it('given the chip is selected, communicates that in an accessible way', () => {
    // ARRANGE
    render(
      <SelectableChip isSelected={true} onClick={() => {}}>
        Test Chip
      </SelectableChip>,
    );

    // ASSERT
    const chipButton = screen.getByRole('button', { name: /test chip/i });

    expect(chipButton).toBeVisible();
    expect(chipButton).toHaveAttribute('aria-pressed', 'true');
  });

  it('given the user clicks the chip, calls the onClick handler', async () => {
    // ARRANGE
    const mockOnClick = vi.fn();
    render(
      <SelectableChip isSelected={false} onClick={mockOnClick}>
        Test Chip
      </SelectableChip>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test chip/i }));

    // ASSERT
    expect(mockOnClick).toHaveBeenCalledTimes(1);
  });

  it('given children are passed to the component, renders the children correctly', () => {
    // ARRANGE
    render(
      <SelectableChip isSelected={false} onClick={() => {}}>
        <span data-testid="child-element">Custom Content</span>
      </SelectableChip>,
    );

    // ASSERT
    expect(screen.getByTestId('child-element')).toBeVisible();
    expect(screen.getByText(/custom content/i)).toBeVisible();
  });
});
