import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createMessage,
  createMessageThread,
  createPaginatedData,
  createUser,
} from '@/test/factories';

import { MessagesShowRoot } from './MessagesShowRoot';

// Suppress "Error: Not implemented: window.scrollTo".
console.error = vi.fn();

describe('Component: MessagesShowRoot', () => {
  let confirmSpy: any;

  beforeEach(() => {
    confirmSpy = vi.spyOn(window, 'confirm');

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);

    const { container } = render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user can reply, shows the reply form', () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true, // !!
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByPlaceholderText(/enter your message here/i)).toBeVisible();
  });

  it('given the user cannot reply, shows a message explaining why', () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: false, // !!
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText(/can't reply to this conversation/i)).toBeVisible();
    expect(screen.queryByPlaceholderText(/enter your message here/i)).not.toBeInTheDocument();
  });

  it('given there are messages, renders all of them', () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const messages = [createMessage({ body: 'Message 1' }), createMessage({ body: 'Message 2' })];
    const paginatedMessages = createPaginatedData(messages);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: false,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText('Message 1')).toBeVisible();
    expect(screen.getByText('Message 2')).toBeVisible();
  });

  it('given the user previews a message, shows the preview content', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox'), 'hello world');
    await screen.getByRole('button', { name: /preview/i }).click();

    // ASSERT
    expect(screen.getAllByText(/hello world/i).length).toEqual(2); // textarea and preview div
  });

  it('given the user paginates, changes the current route correctly', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()], {
      currentPage: 1,
      lastPage: 5,
      links: {
        firstPageUrl: 'first',
        lastPageUrl: 'last',
        nextPageUrl: 'next',
        previousPageUrl: null,
      },
    });

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    const comboboxEl = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(comboboxEl, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledWith(
      route('message-thread.show', {
        messageThread: messageThread.id,
        _query: { page: 2 },
      }),
    );
  });

  it('given the user confirms thread deletion, deletes the thread and redirects', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);
    confirmSpy.mockImplementationOnce(() => true);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true,
        senderUser: createUser({ displayName: 'Scott' }),
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    await waitFor(() => {
      expect(visitSpy).toHaveBeenCalledWith(route('message-thread.index'));
    });
  });

  it('given the user confirms thread deletion while delegating, deletes the thread and redirects', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);
    confirmSpy.mockImplementationOnce(() => true);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true,
        senderUser: createUser({ displayName: 'RAdmin' }),
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    await waitFor(() => {
      expect(visitSpy).toHaveBeenCalledWith(route('message-thread.user.index', { user: 'RAdmin' }));
    });
  });

  it('given the user cancels thread deletion, does not delete the thread', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([createMessage()]);
    confirmSpy.mockImplementationOnce(() => false);

    render(<MessagesShowRoot />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        canReply: true,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(visitSpy).not.toHaveBeenCalled();
  });
});
