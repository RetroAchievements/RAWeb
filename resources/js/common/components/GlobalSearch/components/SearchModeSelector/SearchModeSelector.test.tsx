import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { SearchModeSelector } from './SearchModeSelector';

describe('Component: SearchModeSelector', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const mockOnChange = vi.fn();

    const { container } = render(<SearchModeSelector onChange={mockOnChange} selectedMode="all" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays all search mode options', () => {
    // ARRANGE
    const mockOnChange = vi.fn();

    render(<SearchModeSelector onChange={mockOnChange} selectedMode="all" />);

    // ASSERT
    expect(screen.getByText(/all/i)).toBeVisible();
    expect(screen.getByText(/games/i)).toBeVisible();
    expect(screen.getByText(/hubs/i)).toBeVisible();
    expect(screen.getByText(/users/i)).toBeVisible();
    expect(screen.getByText(/achievements/i)).toBeVisible();
  });

  it('given a selected mode, marks the correct chip as selected', () => {
    // ARRANGE
    const mockOnChange = vi.fn();

    render(<SearchModeSelector onChange={mockOnChange} selectedMode="games" />);

    // ACT
    const gamesButton = screen.getByRole('button', { name: /games/i });
    const allButton = screen.getByRole('button', { name: /all/i });
    const hubsButton = screen.getByRole('button', { name: /hubs/i });

    // ASSERT
    expect(gamesButton).toHaveAttribute('aria-pressed', 'true');
    expect(allButton).toHaveAttribute('aria-pressed', 'false');
    expect(hubsButton).toHaveAttribute('aria-pressed', 'false');
  });

  it('given the user clicks on a chip, calls onChange with the correct value', async () => {
    // ARRANGE
    const mockOnChange = vi.fn();

    render(<SearchModeSelector onChange={mockOnChange} selectedMode="all" />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /games/i }));

    // ASSERT
    expect(mockOnChange).toHaveBeenCalledWith('games');
    expect(mockOnChange).toHaveBeenCalledTimes(1);
  });

  it('given the user clicks on different chips, calls onChange with the appropriate values', async () => {
    // ARRANGE
    const mockOnChange = vi.fn();

    render(<SearchModeSelector onChange={mockOnChange} selectedMode="all" />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /hubs/i }));
    await userEvent.click(screen.getByRole('button', { name: /users/i }));
    await userEvent.click(screen.getByRole('button', { name: /achievements/i }));
    await userEvent.click(screen.getByRole('button', { name: /all/i }));

    // ASSERT
    expect(mockOnChange).toHaveBeenNthCalledWith(1, 'hubs');
    expect(mockOnChange).toHaveBeenNthCalledWith(2, 'users');
    expect(mockOnChange).toHaveBeenNthCalledWith(3, 'achievements');
    expect(mockOnChange).toHaveBeenNthCalledWith(4, 'all');
    expect(mockOnChange).toHaveBeenCalledTimes(4);
  });

  it('renders all chips as accessible buttons', () => {
    // ARRANGE
    const mockOnChange = vi.fn();
    render(<SearchModeSelector onChange={mockOnChange} selectedMode="all" />);

    // ACT
    const buttons = screen.getAllByRole('button');

    // ASSERT
    expect(buttons).toHaveLength(5);
  });
});
