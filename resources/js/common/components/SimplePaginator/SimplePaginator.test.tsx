import { faker } from '@faker-js/faker';

import { render, screen } from '@/test';
import { createPaginatedData } from '@/test/factories';

import { SimplePaginator } from './SimplePaginator';

describe('Component: SimplePaginator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SimplePaginator paginatedData={createPaginatedData([])} />, {
      pageProps: {
        paginatedTopics: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no pages, renders nothing', () => {
    // ARRANGE
    render(<SimplePaginator paginatedData={createPaginatedData([])} />, {
      pageProps: {
        paginatedTopics: createPaginatedData([]),
      },
    });

    // ASSERT
    const linkEls = screen.queryAllByRole('link');
    expect(linkEls.length).toEqual(0);
  });

  it('given there are pages, renders pagination', () => {
    // ARRANGE
    const nextPageUrl = faker.internet.url();
    const previousPageUrl = faker.internet.url();

    render(
      <SimplePaginator
        paginatedData={createPaginatedData([], {
          perPage: 25,
          links: { nextPageUrl, previousPageUrl, firstPageUrl: null, lastPageUrl: null },
        })}
      />,
    );

    // ASSERT
    const previousLinkEl = screen.getByRole('link', { name: /previous/i });
    expect(previousLinkEl).toBeVisible();
    expect(previousLinkEl).toHaveAttribute('href', expect.stringContaining(previousPageUrl));

    const nextLinkEl = screen.getByRole('link', { name: /next/i });
    expect(nextLinkEl).toBeVisible();
    expect(nextLinkEl).toHaveAttribute('href', expect.stringContaining(nextPageUrl));
  });
});
