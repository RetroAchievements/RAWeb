import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { DataTablePaginationControls } from './DataTablePaginationControls';

describe('Component: DataTablePaginationControls', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DataTablePaginationControls
        currentPage={1}
        lastPage={5}
        onPageChange={vi.fn()}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is on the first page, disables the first and previous buttons but not the next and last buttons', () => {
    // ARRANGE
    render(
      <DataTablePaginationControls
        currentPage={1}
        lastPage={5}
        onPageChange={vi.fn()}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: 'Go to first page' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Go to previous page' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Go to next page' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Go to last page' })).toBeEnabled();
  });

  it('given the user is on the last page, disables the next and last buttons but not the first and previous buttons', () => {
    // ARRANGE
    render(
      <DataTablePaginationControls
        currentPage={5}
        lastPage={5}
        onPageChange={vi.fn()}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: 'Go to first page' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Go to previous page' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Go to next page' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Go to last page' })).toBeDisabled();
  });

  it('given the user clicks the next page button, navigates forward and warms the cache for the next page', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    const onPrefetchPage = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={2}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={onPrefetchPage}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(3);
    expect(onPrefetchPage).toHaveBeenCalledWith(4);
  });

  it('given the user clicks the previous page button toward the first page, navigates back', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    const onPrefetchPage = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={2}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={onPrefetchPage}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to previous page' }));

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(1);

    expect(onPrefetchPage).toHaveBeenCalledWith(1);
    expect(onPrefetchPage).not.toHaveBeenCalledWith(0);
  });

  it('given the user clicks the first page button, navigates to the first page', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={4}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to first page' }));

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(1);
  });

  it('given the user clicks the last page button, navigates to the last page', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={2}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to last page' }));

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(5);
  });

  it('given the user hovers over a pagination button, warms the cache', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    const onPrefetchPage = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={2}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={onPrefetchPage}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    expect(onPrefetchPage).toHaveBeenCalledWith(3);
    expect(onPageChange).not.toHaveBeenCalled();
  });

  it('given the user types a valid page number, navigates there after a brief debounce', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={1}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ACT
    const inputEl = screen.getByRole('spinbutton', { name: 'current page number' });
    await userEvent.clear(inputEl);
    await userEvent.type(inputEl, '3');

    // ASSERT
    await waitFor(() => {
      expect(onPageChange).toHaveBeenCalledWith(3);
    });
  });

  it('given the user types an out of bounds page, does not navigate', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(
      <DataTablePaginationControls
        currentPage={1}
        lastPage={5}
        onPageChange={onPageChange}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ACT
    const inputEl = screen.getByRole('spinbutton', { name: 'current page number' });
    await userEvent.clear(inputEl);
    await userEvent.type(inputEl, '999');
    await new Promise((resolve) => setTimeout(resolve, 1200)); // wait for the debounce

    // ASSERT
    expect(onPageChange).not.toHaveBeenCalled();
  });

  it('given the table goes to a new page, the manual entry paginator field updates accordingly', () => {
    // ARRANGE
    const { rerender } = render(
      <DataTablePaginationControls
        currentPage={1}
        lastPage={5}
        onPageChange={vi.fn()}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ACT
    rerender(
      <DataTablePaginationControls
        currentPage={4}
        lastPage={5}
        onPageChange={vi.fn()}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('spinbutton', { name: 'current page number' })).toHaveValue(4);
  });

  it('given the user changes the page, scrolls back to the top of the list', async () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    render(
      <div>
        <div id="pagination-scroll-target" />
        <DataTablePaginationControls
          currentPage={2}
          lastPage={5}
          onPageChange={vi.fn()}
          onPrefetchPage={vi.fn()}
        />
      </div>,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    await waitFor(() => {
      expect(mockScrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' });
    });
  });

  it('given the scroll target is not on the page, does not scroll', async () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    render(
      <DataTablePaginationControls
        currentPage={2}
        lastPage={5}
        onPageChange={vi.fn()}
        onPrefetchPage={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));
    await new Promise((resolve) => setTimeout(resolve, 50)); // wait for the queued scroll event

    // ASSERT
    expect(mockScrollTo).not.toHaveBeenCalled();
  });

  it('given there is only a single page, shows static text instead of a manual page field', () => {
    // ARRANGE
    render(<DataTablePaginationControls currentPage={1} lastPage={1} onPageChange={vi.fn()} />);

    // ASSERT
    expect(screen.queryByRole('spinbutton')).not.toBeInTheDocument();
    expect(screen.getByText(/page 1 of 1/i)).toBeVisible();
  });

  it('given no prefetch callback is provided, navigation still works', async () => {
    // ARRANGE
    const onPageChange = vi.fn();
    render(
      <DataTablePaginationControls currentPage={2} lastPage={5} onPageChange={onPageChange} />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Go to next page' }));

    // ASSERT
    expect(onPageChange).toHaveBeenCalledWith(3);
  });
});
