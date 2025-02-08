import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createMessageThread, createPaginatedData } from '@/test/factories';

import { MessagesIndexRoot } from './MessagesIndexRoot';

describe('Component: MessagesIndexRoot', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays breadcrumbs', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /messages/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: /scott/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: /inbox/i })).toBeVisible();
  });

  it('displays the correct message counts', () => {
    // ARRANGE
    const threads = [createMessageThread(), createMessageThread()];

    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData(threads, { total: 2 }),
        unreadMessageCount: 1,
      },
    });

    // ASSERT
    expect(screen.getByText(/1 unread message in 2 message threads/i)).toBeVisible();
  });

  it('has accessible pagination links', async () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
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

  it('given a user selects a page number option, navigates them to that page', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData(
          [createMessageThread(), createMessageThread()],
          {
            perPage: 1,
            lastPage: 2,
            currentPage: 1,
            links: {
              previousPageUrl: null,
              firstPageUrl: null,
              nextPageUrl: '#',
              lastPageUrl: '#',
            },
          },
        ),
        unreadMessageCount: 0,
      },
    });

    // ACT
    const paginatorCombobox = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(paginatorCombobox, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith(['message-thread.index', { _query: { page: 2 } }]);
  });

  it('displays a link to create a new message', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /new message/i })).toBeVisible();
  });
});
