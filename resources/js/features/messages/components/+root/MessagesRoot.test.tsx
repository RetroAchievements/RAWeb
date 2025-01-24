import { render, screen } from '@/test';
import { createMessageThread, createPaginatedData } from '@/test/factories';

import { MessagesRoot } from './MessagesRoot';

vi.mock('@inertiajs/react', () => ({
  router: {
    visit: vi.fn(),
  },
}));

describe('Component: MessagesRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagesRoot />, {
      pageProps: {
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct message counts', () => {
    // ARRANGE
    const threads = [createMessageThread(), createMessageThread()];

    render(<MessagesRoot />, {
      pageProps: {
        paginatedMessageThreads: createPaginatedData(threads, { total: 2 }),
        unreadMessageCount: 1,
      },
    });

    // ASSERT
    expect(screen.getByText(/1 unread message in 2 message threads/i)).toBeVisible();
  });

  it('has accessible pagination links', async () => {
    // ARRANGE
    render(<MessagesRoot />, {
      pageProps: {
        paginatedMessageThreads: createPaginatedData([createMessageThread()], {
          currentPage: 1,
          lastPage: 2,
          total: 20,
          perPage: 10,
          links: {
            previousPageUrl: null,
            firstPageUrl: null,
            nextPageUrl: '#',
            lastPageUrl: '#',
          },
        }),
        unreadMessageCount: 0,
      },
    });

    // ASSERT
    expect(
      screen.getAllByRole('navigation', { name: /pagination/i }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('displays a link to create a new message', () => {
    // ARRANGE
    render(<MessagesRoot />, {
      pageProps: {
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /new message/i })).toBeVisible();
  });
});
