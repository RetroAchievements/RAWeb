/* eslint-disable testing-library/no-node-access */

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
      {
        pageProps: { ziggy: { device: 'desktop' } as any },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no previous page url and no next page url, renders nothing', () => {
    // ARRANGE
    render(
      <FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={createPaginatedData([])} />,
      {
        pageProps: { ziggy: { device: 'desktop' } as any },
      },
    );

    // ASSERT
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
    expect(screen.queryByText(/page/i)).not.toBeInTheDocument();
  });

  it('given the user is on the first page, renders disabled buttons to go back to previous pages', () => {
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

    render(<FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={paginatedData} />, {
      pageProps: { ziggy: { device: 'desktop' } as any },
    });

    // ASSERT
    const firstPageLink = screen.getByLabelText('Go to first page').querySelector('a');
    const prevPageLink = screen.getByLabelText('Go to previous page').querySelector('a');

    expect(firstPageLink).toHaveAttribute('aria-disabled', 'true');
    expect(prevPageLink).toHaveAttribute('aria-disabled', 'true');

    const nextPageLink = screen.getByLabelText('Go to next page').querySelector('a');
    const lastPageLink = screen.getByLabelText('Go to last page').querySelector('a');

    expect(nextPageLink).not.toHaveAttribute('aria-disabled');
    expect(lastPageLink).not.toHaveAttribute('aria-disabled');
  });

  it('given the user is on the last page, renders disabled buttons to go to next pages', () => {
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

    render(<FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={paginatedData} />, {
      pageProps: { ziggy: { device: 'desktop' } as any },
    });

    // ASSERT
    const firstPageLink = screen.getByLabelText('Go to first page').querySelector('a');
    const prevPageLink = screen.getByLabelText('Go to previous page').querySelector('a');

    expect(firstPageLink).not.toHaveAttribute('aria-disabled');
    expect(prevPageLink).not.toHaveAttribute('aria-disabled');

    const nextPageLink = screen.getByLabelText('Go to next page').querySelector('a');
    const lastPageLink = screen.getByLabelText('Go to last page').querySelector('a');

    expect(nextPageLink).toHaveAttribute('aria-disabled', 'true');
    expect(lastPageLink).toHaveAttribute('aria-disabled', 'true');
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
      {
        pageProps: { ziggy: { device: 'desktop' } as any },
      },
    );

    // ACT
    await userEvent.selectOptions(screen.getByRole('combobox'), ['2']);

    // ASSERT
    expect(onPageSelectValueChange).toHaveBeenCalledWith(2);
  });

  it('given the user is on a mobile device, uses condensed option labels', () => {
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

    render(<FullPaginator onPageSelectValueChange={vi.fn()} paginatedData={paginatedData} />, {
      pageProps: { ziggy: { device: 'mobile' } as any },
    });

    // ASSERT
    expect(screen.getByRole('option', { name: '1' })).toBeVisible();
    expect(screen.queryByRole('option', { name: /page/i })).not.toBeInTheDocument();
  });
});
