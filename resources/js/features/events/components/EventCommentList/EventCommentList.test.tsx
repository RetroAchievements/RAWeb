import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createComment, createRaEvent } from '@/test/factories';

import { EventCommentList } from './EventCommentList';

describe('Component: EventCommentList', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are 20 or fewer comments, displays "Comments:" heading', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 15,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/^comments:$/i)).toBeVisible();
    expect(screen.queryByText(/^recent comments:$/i)).not.toBeInTheDocument();
  });

  it('given there are more than 20 comments, displays "Recent comments:" heading', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 25,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByText(/^recent comments:$/i)).toBeVisible();
    expect(screen.queryByText(/^comments:$/i)).not.toBeInTheDocument();
  });

  it('given there are more than 20 comments, shows the "all X" link', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 50,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /all 50/i })).toBeVisible();
  });

  it('given there are 20 or fewer comments, does not show the "all X" link', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 15,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /all/i })).not.toBeInTheDocument();
  });

  it('displays the subscribe toggle button', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /subscribe/i })).toBeVisible();
  });

  it('given canComment is true, shows the comment input field', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { createEventComments: true },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('textbox')).toBeVisible();
  });

  it('given canComment is false, does not show the comment input field', () => {
    // ARRANGE
    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        can: { createEventComments: false },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('given the user submits a comment, reloads comments via Inertia', async () => {
    // ARRANGE
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { createEventComments: true },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox'), 'this is my new comment');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(reloadSpy).toHaveBeenCalledOnce();
    expect(reloadSpy).toHaveBeenCalledWith({ only: ['recentVisibleComments'] });
  });

  it('given the user deletes a comment, reloads comments via Inertia', async () => {
    // ARRANGE
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);

    render<App.Platform.Data.EventShowPageProps>(<EventCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { createEventComments: true },
        event: createRaEvent({ id: 1 }),
        isSubscribedToComments: false,
        numComments: 0,
        recentVisibleComments: [createComment({ canDelete: true })],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(reloadSpy).toHaveBeenCalledOnce();
    expect(reloadSpy).toHaveBeenCalledWith({ only: ['recentVisibleComments'] });
  });
});
