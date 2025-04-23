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
        selectableInboxDisplayNames: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is unauthenticated, renders nothing', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: null, // !!
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
        selectableInboxDisplayNames: [],
      },
    });

    // ASSERT
    expect(screen.queryByText(/inbox/i)).not.toBeInTheDocument();
  });

  it('given the user can view multiple inboxes, shows a button to let them change the current inbox', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
        selectableInboxDisplayNames: ['Scott', 'RAdmin'], // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /change inbox/i })).toBeVisible();
  });

  it('given the user cannot view multiple inboxes, does not show a change inbox button', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
        selectableInboxDisplayNames: ['Scott'], // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /change inbox/i })).not.toBeInTheDocument();
  });

  it('displays breadcrumbs', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
        selectableInboxDisplayNames: ['Scott'],
        senderUserDisplayName: 'Scott',
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /your inbox/i })).toBeVisible();
  });

  it('displays the correct message counts', () => {
    // ARRANGE
    const threads = [createMessageThread(), createMessageThread()];

    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData(threads, { total: 2 }),
        unreadMessageCount: 1,
        selectableInboxDisplayNames: [],
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
        selectableInboxDisplayNames: [],
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
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
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
        senderUserDisplayName: 'Scott',
        unreadMessageCount: 0,
        selectableInboxDisplayNames: [],
      },
    });

    // ACT
    const paginatorCombobox = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(paginatorCombobox, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith(['message-thread.index', { _query: { page: 2 } }]);
  });

  it('given the user is delegating and selects a page number option, navigates them to that page with the correct URL', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
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
        senderUserDisplayName: 'RAdmin',
        unreadMessageCount: 0,
        selectableInboxDisplayNames: [],
      },
    });

    // ACT
    const paginatorCombobox = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(paginatorCombobox, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith([
      'message-thread.user.index',
      { user: 'RAdmin', _query: { page: 2 } },
    ]);
  });

  it('displays a link to create a new message', () => {
    // ARRANGE
    render(<MessagesIndexRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        paginatedMessageThreads: createPaginatedData([]),
        unreadMessageCount: 0,
        selectableInboxDisplayNames: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /new message/i })).toBeVisible();
  });
});
