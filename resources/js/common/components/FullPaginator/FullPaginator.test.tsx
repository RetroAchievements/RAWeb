import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createGame, createPaginatedData } from '@/test/factories';

import { FullPaginator } from './FullPaginator';

describe('Component: FullPaginator', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={createPaginatedData([])} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no previous page url and no next page url, renders nothing', () => {
    // ARRANGE
    render(
      <FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={createPaginatedData([])} />,
    );

    // ASSERT
    expect(screen.queryByRole('pagination')).not.toBeInTheDocument();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
    expect(screen.queryByText(/page/i)).not.toBeInTheDocument();
  });

  it('given the user is on the first page, does not render buttons to go back to previous pages', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([createGame(), createGame()], {
      perPage: 1,
      lastPage: 2,
      currentPage: 1,
      links: {
        previousPageUrl: null,
        firstPageUrl: null,
        nextPageUrl: '#',
        lastPageUrl: '#',
      },
    });

    render(<FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={paginatedData} />);

    // ASSERT
    expect(screen.queryByRole('listitem', { name: /go to first page/i })).not.toBeInTheDocument();
    expect(
      screen.queryByRole('listitem', { name: /go to previous page/i }),
    ).not.toBeInTheDocument();

    expect(screen.getByRole('listitem', { name: /go to next page/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: /go to last page/i })).toBeVisible();
  });

  it('given the user is on the last page, does not render buttons to go on to next pages', () => {
    // ARRANGE
    const paginatedData = createPaginatedData([createGame(), createGame()], {
      perPage: 1,
      lastPage: 2,
      currentPage: 2,
      links: {
        previousPageUrl: '#',
        firstPageUrl: '#',
        nextPageUrl: null,
        lastPageUrl: null,
      },
    });

    render(<FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={paginatedData} />);

    // ASSERT
    expect(screen.getByRole('listitem', { name: /go to first page/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: /go to previous page/i })).toBeVisible();

    expect(screen.queryByRole('listitem', { name: /go to next page/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('listitem', { name: /go to last page/i })).not.toBeInTheDocument();
  });

  it('given the user manually selects a page, emits an event', async () => {
    // ARRANGE
    const onPageSelectValueChange = vi.fn();

    const paginatedData = createPaginatedData([createGame(), createGame()], {
      perPage: 1,
      lastPage: 2,
      currentPage: 1,
      links: {
        previousPageUrl: null,
        firstPageUrl: null,
        nextPageUrl: '#',
        lastPageUrl: '#',
      },
    });

    render(
      <FullPaginator
        onPageSelectValueChange={onPageSelectValueChange}
        paginatedData={paginatedData}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByRole('option', { name: '2' }));

    // ASSERT
    expect(onPageSelectValueChange).toHaveBeenCalledWith(2);
  });
});
