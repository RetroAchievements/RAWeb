import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { SearchPagination } from './SearchPagination';

describe('Component: SearchPagination', () => {
  let scrollToSpy: ReturnType<typeof vi.spyOn>;

  beforeEach(() => {
    scrollToSpy = vi.spyOn(window, 'scrollTo').mockImplementation(vi.fn());
  });

  afterEach(() => {
    scrollToSpy.mockRestore();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SearchPagination currentPage={1} lastPage={5} onPageChange={vi.fn()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given lastPage is 1, does not render pagination', () => {
    // ARRANGE
    render(<SearchPagination currentPage={1} lastPage={1} onPageChange={vi.fn()} />);

    // ASSERT
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
  });

  it('given lastPage is greater than 1, renders pagination controls', () => {
    // ARRANGE
    render(<SearchPagination currentPage={1} lastPage={5} onPageChange={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('navigation')).toBeVisible();
    expect(screen.getByRole('combobox')).toBeVisible();
  });

  it('renders the correct number of page options in the select dropdown', () => {
    // ARRANGE
    render(<SearchPagination currentPage={1} lastPage={5} onPageChange={vi.fn()} />);

    // ASSERT
    const options = screen.getAllByRole('option');

    expect(options.length).toEqual(5);
  });

  it('given the user is on page 1, disables the first and previous buttons', () => {
    // ARRANGE
    render(<SearchPagination currentPage={1} lastPage={5} onPageChange={vi.fn()} />);

    // ASSERT
    const buttons = screen.getAllByRole('button');
    const firstButton = buttons[0];
    const prevButton = buttons[1];

    expect(firstButton).toBeDisabled();
    expect(prevButton).toBeDisabled();
  });

  it('given the user is on the last page, disables the next and last buttons', () => {
    // ARRANGE
    render(<SearchPagination currentPage={5} lastPage={5} onPageChange={vi.fn()} />);

    // ASSERT
    const buttons = screen.getAllByRole('button');
    const nextButton = buttons[2];
    const lastButton = buttons[3];

    expect(nextButton).toBeDisabled();
    expect(lastButton).toBeDisabled();
  });

  it('given the user is on a middle page, all navigation buttons are enabled', () => {
    // ARRANGE
    render(<SearchPagination currentPage={3} lastPage={5} onPageChange={vi.fn()} />);

    // ASSERT
    const buttons = screen.getAllByRole('button');

    for (const button of buttons) {
      expect(button).not.toBeDisabled();
    }
  });

  it('given the user clicks the first page button, calls onPageChange with 1', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(<SearchPagination currentPage={3} lastPage={5} onPageChange={onPageChange} />);

    // ACT
    const buttons = screen.getAllByRole('button');
    await userEvent.click(buttons[0]);

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(1);
  });

  it('given the user clicks the previous page button, calls onPageChange with the correct page number', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(<SearchPagination currentPage={3} lastPage={5} onPageChange={onPageChange} />);

    // ACT
    const buttons = screen.getAllByRole('button');
    await userEvent.click(buttons[1]);

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(2); // currentPage - 1
  });

  it('given the user clicks the next page button, calls onPageChange with the correct page number', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(<SearchPagination currentPage={3} lastPage={5} onPageChange={onPageChange} />);

    // ACT
    const buttons = screen.getAllByRole('button');
    await userEvent.click(buttons[2]);

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(4); // currentPage + 1
  });

  it('given the user clicks the last page button, calls onPageChange with the last page number', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(<SearchPagination currentPage={3} lastPage={5} onPageChange={onPageChange} />);

    // ACT
    const buttons = screen.getAllByRole('button');
    await userEvent.click(buttons[3]);

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(5);
  });

  it('given the user selects a page from the dropdown, calls onPageChange with the selected page', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(<SearchPagination currentPage={1} lastPage={5} onPageChange={onPageChange} />);

    // ACT
    const select = screen.getByRole('combobox');
    await userEvent.selectOptions(select, '4');

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(4);
  });

  it('given the user navigates to a new page, scrolls to top', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(<SearchPagination currentPage={1} lastPage={5} onPageChange={onPageChange} />);

    // ACT
    const buttons = screen.getAllByRole('button');
    await userEvent.click(buttons[2]); // this is the next page button

    // ASSERT
    expect(scrollToSpy).toHaveBeenCalledWith({ top: 0, behavior: 'instant' });
  });
});
