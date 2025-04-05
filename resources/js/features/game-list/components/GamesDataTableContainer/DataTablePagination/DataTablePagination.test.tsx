import type { Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { DataTablePagination } from './DataTablePagination';

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getState: () =>
    ({
      pagination: {
        pageIndex: 0,
        pageSize: 10,
      },
    }) as any,
  getCanPreviousPage: () => false,
  getCanNextPage: () => true,
  getPageCount: () => 5,
  setPageIndex: vi.fn(),
  ...overrides,
});

describe('Component: DataTablePagination', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const mockTable = createMockTable();

    const { container } = render(
      <div>
        <div id="pagination-scroll-target" />
        <DataTablePagination table={mockTable as Table<any>} />
      </div>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is on the first page, disables the previous and first page buttons', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getCanPreviousPage: () => false,
      getState: () => ({
        pagination: { pageIndex: 0, pageSize: 10 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /first page/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /previous page/i })).toBeDisabled();
  });

  it('given the user is on the last page, disables the next and last page buttons', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getCanNextPage: () => false,
      getState: () => ({
        pagination: { pageIndex: 4, pageSize: 10 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /next page/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /last page/i })).toBeDisabled();
  });

  it('given the user clicks pagination buttons, navigates to the correct page', async () => {
    // ARRANGE
    const setPageIndex = vi.fn();

    const mockTable = createMockTable({
      setPageIndex,
      getState: () => ({
        pagination: { pageIndex: 2, pageSize: 10 },
      }),
      getCanPreviousPage: () => true,
      getCanNextPage: () => true,
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByLabelText(/go to next page/i));
    await userEvent.click(screen.getByLabelText(/go to previous page/i));
    await userEvent.click(screen.getByLabelText(/go to first page/i));
    await userEvent.click(screen.getByLabelText(/go to last page/i));

    // ASSERT
    expect(setPageIndex).toHaveBeenCalledTimes(4);

    expect(setPageIndex).toHaveBeenNthCalledWith(1, 3);
    expect(setPageIndex).toHaveBeenNthCalledWith(2, 1);
    expect(setPageIndex).toHaveBeenNthCalledWith(3, 0);
    expect(setPageIndex).toHaveBeenNthCalledWith(4, 4);
  });

  it('allows manual page entry and updates the page', async () => {
    // ARRANGE
    const setPageIndex = vi.fn();

    const mockTable = createMockTable({
      setPageIndex,
      getState: () => ({
        pagination: { pageIndex: 0, pageSize: 10 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ACT
    const inputEl = screen.getByRole('spinbutton', { name: /current page number/i });

    await userEvent.clear(inputEl);
    await userEvent.type(inputEl, '3');

    // ASSERT
    await waitFor(() => {
      expect(setPageIndex).toHaveBeenCalledWith(2); // 0-based page index
    });
  });

  it('does not allow manual page navigation to invalid page numbers', async () => {
    // ARRANGE
    const setPageIndex = vi.fn();

    const mockTable = createMockTable({
      setPageIndex,
      getState: () => ({
        pagination: { pageIndex: 0, pageSize: 10 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ACT
    const inputEl = screen.getByRole('spinbutton', { name: /current page number/i });

    await userEvent.clear(inputEl);
    await userEvent.type(inputEl, '999');
    await new Promise((r) => setTimeout(r, 1500)); // Wait for the debounce.

    // ASSERT
    expect(setPageIndex).not.toHaveBeenCalled();
  });

  it('displays the current page and total pages correctly', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getState: () => ({
        pagination: { pageIndex: 2, pageSize: 10 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('spinbutton')).toHaveValue(3); // 1-based for display.
    expect(screen.getByText(/of 5/i)).toBeVisible();
  });

  it('given there is only a single page, does not render a manual paginator field', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getPageCount: () => 1,
      getState: () => ({
        pagination: { pageIndex: 0, pageSize: 10 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
    expect(screen.getByText(/page 1 of 1/i)).toBeVisible();
  });

  it('allows the user to change the row count per page', async () => {
    // ARRANGE
    const setPagination = vi.fn();

    const mockTable = createMockTable({
      setPagination,
      getState: () => ({
        pagination: { pageIndex: 0, pageSize: 25 },
      }),
    });

    render(<DataTablePagination table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByLabelText(/rows per page/i));
    await userEvent.click(screen.getByRole('option', { name: '50' }));

    // ASSERT
    expect(setPagination).toHaveBeenCalledWith({ pageIndex: 0, pageSize: 50 });
  });

  it('given the user changes page size while not on the first page, scrolls to the top of the page', async () => {
    // ARRANGE
    const mockScrollTo = vi.fn();
    window.scrollTo = mockScrollTo;

    const setPagination = vi.fn();
    const mockTable = createMockTable({
      setPagination,
      getState: () => ({
        pagination: { pageIndex: 2, pageSize: 25 }, // !! user is on page 3
      }),
    });

    render(
      <div>
        <div id="pagination-scroll-target" />
        <DataTablePagination table={mockTable as Table<any>} />
      </div>,
    );

    // ACT
    await userEvent.click(screen.getByLabelText(/rows per page/i));
    await userEvent.click(screen.getByRole('option', { name: '50' }));

    // ASSERT
    expect(mockScrollTo).toHaveBeenCalledWith({
      top: expect.any(Number),
      behavior: 'smooth',
    });
  });
});
