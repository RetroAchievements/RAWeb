import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { SearchInput } from './SearchInput';

describe('Component: SearchInput', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SearchInput isLoading={false} onSearch={vi.fn()} query="" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a textbox with the correct placeholder', () => {
    // ARRANGE
    render(<SearchInput isLoading={false} onSearch={vi.fn()} query="" />);

    // ASSERT
    const input = screen.getAllByRole('textbox')[0];

    expect(input).toBeVisible();
    expect(input).toHaveAttribute(
      'placeholder',
      'Search for games, users, achievements, and more...',
    );
  });

  it('displays the query value in the input', () => {
    // ARRANGE
    render(<SearchInput isLoading={false} onSearch={vi.fn()} query="mario" />);

    // ASSERT
    const input = screen.getAllByRole('textbox')[0];

    expect(input).toHaveValue('mario');
  });

  it('given the user types in the input, calls onSearch with the input value', async () => {
    // ARRANGE
    const onSearch = vi.fn();
    render(<SearchInput isLoading={false} onSearch={onSearch} query="" />);

    // ACT
    const input = screen.getAllByRole('textbox')[0];
    await userEvent.type(input, 'z');

    // ASSERT
    expect(onSearch).toHaveBeenCalledWith('z');
  });

  it('given isLoading is false, does not show the loading spinner', () => {
    // ARRANGE
    render(<SearchInput isLoading={false} onSearch={vi.fn()} query="test" />);

    // ASSERT
    expect(screen.queryByTestId('search-loading-spinner')).not.toBeInTheDocument();
  });

  it('given isLoading is true, shows the loading spinner', () => {
    // ARRANGE
    render(<SearchInput isLoading={true} onSearch={vi.fn()} query="test" />);

    // ASSERT
    expect(screen.getByTestId('search-loading-spinner')).toBeVisible();
  });
});
