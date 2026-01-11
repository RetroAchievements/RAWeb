import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createComment,
  createGame,
  createPaginatedData,
  createRaEvent,
  createSystem,
} from '@/test/factories';

import { EventCommentsMainRoot } from './EventCommentsMainRoot';

describe('Component: EventCommentsMainRoot', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.EventCommentsPageProps>(
      <EventCommentsMainRoot />,
      {
        pageProps: {
          auth: null,
          event: createRaEvent(),
          paginatedComments: createPaginatedData([]),
          isSubscribed: false,
          canComment: false,
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays event breadcrumbs', () => {
    // ARRANGE
    const system = createSystem({ name: 'Events' });
    const game = createGame({ system, title: 'Achievement of the Week' });
    const event = createRaEvent({ legacyGame: game });

    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        event,
        auth: null,
        paginatedComments: createPaginatedData([]),
        isSubscribed: false,
        canComment: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /all events/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: game.title })).toBeVisible();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        event: createRaEvent(),
        auth: null,
        paginatedComments: createPaginatedData([]),
        isSubscribed: false,
        canComment: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /comments/i })).toBeVisible();
  });

  it('displays pagination controls', () => {
    // ARRANGE
    const paginatedComments = createPaginatedData([createComment()], {
      currentPage: 1,
      lastPage: 2,
      perPage: 1,
      total: 2,
      unfilteredTotal: 2,
      links: {
        previousPageUrl: '#',
        firstPageUrl: '#',
        lastPageUrl: '#',
        nextPageUrl: '#',
      },
    });

    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        paginatedComments,
        auth: null,
        event: createRaEvent(),
        isSubscribed: false,
        canComment: false,
      },
    });

    // ASSERT
    expect(
      screen.getAllByRole('navigation', { name: /pagination/i }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('given the user is currently authenticated, displays a subscribe toggle button', () => {
    // ARRANGE
    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        event: createRaEvent(),
        paginatedComments: createPaginatedData([]),
        isSubscribed: false,
        canComment: true,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /subscribe/i })).toBeVisible();
  });

  it('given the user is not currently authenticated, does not display a subscribe toggle button', () => {
    // ARRANGE
    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        auth: null,
        event: createRaEvent(),
        paginatedComments: createPaginatedData([]),
        isSubscribed: false,
        canComment: false,
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /subscribe/i })).not.toBeInTheDocument();
  });

  it('given there are comments, displays them', () => {
    // ARRANGE
    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        auth: null,
        event: createRaEvent(),
        paginatedComments: createPaginatedData([createComment({ payload: '12345678' })]),
        isSubscribed: false,
        canComment: true,
      },
    });

    // ASSERT
    expect(screen.getByText(/12345678/i)).toBeVisible();
  });

  it('given the user paginates, changes the current route correctly', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const paginatedComments = createPaginatedData([createComment()], {
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

    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        paginatedComments,
        auth: null,
        event: createRaEvent({ id: 1 }),
        isSubscribed: false,
        canComment: true,
      },
    });

    // ACT
    const comboboxEl = screen.getAllByRole('combobox')[0];
    await userEvent.click(comboboxEl);
    await userEvent.selectOptions(comboboxEl, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith([
      'event.comment.index',
      { event: 1, _query: { page: 2 } },
    ]);
  });

  it('given the user adds a comment, changes the current route correctly', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const paginatedComments = createPaginatedData([createComment()], {
      perPage: 1, // one per page! ...
      lastPage: 2, // ... so adding a new comment should take the user to page 3
      currentPage: 1,
      total: 2,
      links: {
        previousPageUrl: null,
        firstPageUrl: null,
        nextPageUrl: '#',
        lastPageUrl: '#',
      },
    });

    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        paginatedComments,
        auth: { user: createAuthenticatedUser() }, // we're logged in, so we can write comments
        event: createRaEvent({ id: 1 }),
        isSubscribed: false,
        canComment: true,
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox'), 'this is my new comment');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith(
      ['event.comment.index', { event: 1, _query: { page: 3 } }],
      { preserveScroll: true },
    );
  });

  it('given the user deletes a comment, changes the current route correctly', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);

    const paginatedComments = createPaginatedData([createComment({ canDelete: true })], {
      perPage: 1, // one per page! ...
      lastPage: 2, // ... so deleting a new comment should take the user to page 1
      currentPage: 2,
      total: 2,
      links: {
        previousPageUrl: null,
        firstPageUrl: null,
        nextPageUrl: '#',
        lastPageUrl: '#',
      },
    });

    render<App.Community.Data.EventCommentsPageProps>(<EventCommentsMainRoot />, {
      pageProps: {
        paginatedComments,
        auth: { user: createAuthenticatedUser() }, // we're logged in
        event: createRaEvent({ id: 1 }),
        isSubscribed: false,
        canComment: true,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith(
      ['event.comment.index', { event: 1, _query: { page: 1 } }],
      { preserveScroll: true },
    );
  });
});
