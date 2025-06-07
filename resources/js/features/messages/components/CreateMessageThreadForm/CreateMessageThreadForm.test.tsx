import { faker } from '@faker-js/faker';
import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createUser } from '@/test/factories';

import { CreateMessageThreadForm } from './CreateMessageThreadForm';

// Suppress JSDOM errors that are not relevant.
console.error = vi.fn();

describe('Component: CreateMessageThreadForm', () => {
  beforeEach(() => {
    window.scrollTo = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: '',
        subject: '',
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is sending a message as themselves, shows the correct submit button label', () => {
    // ARRANGE
    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) }, // !!
        senderUserDisplayName: 'Scott', // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'Submit' })).toBeVisible();
  });

  it('given the user is sending a message on behalf of a team, shows the correct submit button label', () => {
    // ARRANGE
    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) }, // !!
        senderUserDisplayName: 'RAdmin', // !!
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'Submit (as RAdmin)' })).toBeVisible();
  });

  it('given the form is submitted with valid data, posts to the API and redirects on success', async () => {
    // ARRANGE
    const mockUser = createUser({
      id: 1,
      displayName: faker.internet.username().slice(0, 20),
      avatarUrl: faker.image.avatar(),
    });

    vi.spyOn(axios, 'get').mockImplementation((url) => {
      if (url.includes('api.search.index')) {
        return Promise.resolve({
          data: {
            results: { users: [mockUser] },
            query: mockUser.displayName,
            scopes: ['users'],
            scopeRelevance: { users: 1 },
          },
        });
      }

      return Promise.reject(new Error('Not mocked'));
    });

    const mockThreadId = faker.number.int();
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValue({ data: { threadId: mockThreadId } });
    const routerSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    // ... first handle the recipient select ...
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.type(screen.getByPlaceholderText(/type a username/i), mockUser.displayName);
    await waitFor(() => {
      expect(screen.getByText(mockUser.displayName)).toBeVisible();
    });
    await userEvent.click(screen.getByText(mockUser.displayName));

    // ... then fill out the rest of the form ...
    await userEvent.type(
      screen.getByPlaceholderText(/enter your message's subject/i),
      'this is my subject',
    );
    await userEvent.type(
      screen.getByPlaceholderText(/don't ask for links/i),
      'this is my message content',
    );
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.message.store'),
        expect.objectContaining({
          recipient: mockUser.displayName,
          title: 'this is my subject',
          body: 'this is my message content',
        }),
      );
    });

    expect(routerSpy).toHaveBeenCalledOnce();
  });

  it('given the recipient is not accepting messages, shows an error message', async () => {
    // ARRANGE
    const mockUser = createUser({
      id: 1,
      displayName: faker.internet.username().slice(0, 20),
      avatarUrl: faker.image.avatar(),
    });

    vi.spyOn(axios, 'get').mockImplementation((url) => {
      if (url.includes('api.search.index')) {
        return Promise.resolve({
          data: {
            results: { users: [mockUser] },
            query: mockUser.displayName,
            scopes: ['users'],
            scopeRelevance: { users: 1 },
          },
        });
      }

      return Promise.reject(new Error('Not mocked'));
    });

    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: { data: { error: 'cannot_message_user' } },
    });

    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.type(screen.getByPlaceholderText(/type a username/i), mockUser.displayName);
    await waitFor(() => {
      expect(screen.getByText(mockUser.displayName)).toBeVisible();
    });
    await userEvent.click(screen.getByText(mockUser.displayName));

    await userEvent.type(
      screen.getByPlaceholderText(/enter your message's subject/i),
      'this is my subject',
    );
    await userEvent.type(
      screen.getByPlaceholderText(/don't ask for links/i),
      'this is my message content',
    );
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/this user isn't accepting messages right now/i)).toBeVisible();
    });
  });

  it('given an unexpected error occurs, shows a generic error message', async () => {
    // ARRANGE
    const mockUser = createUser({
      id: 1,
      displayName: faker.internet.username().slice(0, 20),
      avatarUrl: faker.image.avatar(),
    });

    vi.spyOn(axios, 'get').mockImplementation((url) => {
      if (url.includes('api.search.index')) {
        return Promise.resolve({
          data: {
            results: { users: [mockUser] },
            query: mockUser.displayName,
            scopes: ['users'],
            scopeRelevance: { users: 1 },
          },
        });
      }

      return Promise.reject(new Error('Not mocked'));
    });

    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: { data: { error: 'unexpected_error' } },
    });

    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.type(screen.getByPlaceholderText(/type a username/i), mockUser.displayName);
    await waitFor(() => {
      expect(screen.getByText(mockUser.displayName)).toBeVisible();
    });
    await userEvent.click(screen.getByText(mockUser.displayName));

    await userEvent.type(
      screen.getByPlaceholderText(/enter your message's subject/i),
      'this is my subject',
    );
    await userEvent.type(
      screen.getByPlaceholderText(/don't ask for links/i),
      'this is my message content',
    );
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('given the preview button is clicked, calls the preview handler with the message body', async () => {
    // ARRANGE
    const previewHandler = vi.fn();

    render(<CreateMessageThreadForm onPreview={previewHandler} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/don't ask for links/i),
      'this is my message content',
    );
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(previewHandler).toHaveBeenCalledWith('this is my message content');
  });

  it('given a user is pre-selected, disables the recipient field', () => {
    // ARRANGE
    const mockUser = createUser({
      id: 1,
      displayName: faker.internet.username().slice(0, 20),
      avatarUrl: faker.image.avatar(),
    });

    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        toUser: mockUser,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByRole('combobox')).toBeDisabled();
  });

  it('given a subject is pre-filled, disables the subject field', () => {
    // ARRANGE
    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: 'pre-filled subject',
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByPlaceholderText(/enter your message's subject/i)).toBeDisabled();
  });

  it('given the user is muted, shows an error message', async () => {
    // ARRANGE
    const mockUser = createUser({
      id: 1,
      displayName: faker.internet.username().slice(0, 20),
      avatarUrl: faker.image.avatar(),
    });

    vi.spyOn(axios, 'get').mockImplementation((url) => {
      if (url.includes('api.search.index')) {
        return Promise.resolve({
          data: {
            results: { users: [mockUser] },
            query: mockUser.displayName,
            scopes: ['users'],
            scopeRelevance: { users: 1 },
          },
        });
      }

      return Promise.reject(new Error('Not mocked'));
    });

    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: { data: { error: 'muted_user' } },
    });

    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: null,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.type(screen.getByPlaceholderText(/type a username/i), mockUser.displayName);
    await waitFor(() => {
      expect(screen.getByText(mockUser.displayName)).toBeVisible();
    });
    await userEvent.click(screen.getByText(mockUser.displayName));

    await userEvent.type(
      screen.getByPlaceholderText(/enter your message's subject/i),
      'this is my subject',
    );
    await userEvent.type(
      screen.getByPlaceholderText(/don't ask for links/i),
      'this is my message content',
    );
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/muted users can only message team accounts/i)).toBeVisible();
    });
  });

  it('given a templateKind is provided, renders the TemplateKindAlert', () => {
    // ARRANGE
    render(<CreateMessageThreadForm onPreview={() => {}} />, {
      pageProps: {
        message: null,
        subject: null,
        templateKind: 'manual-unlock',
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByText(/important/i)).toBeVisible();
  });
});
