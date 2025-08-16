import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor, within } from '@/test';
import { createForum, createForumCategory, createUser } from '@/test/factories';

import { CreateTopicForm } from './CreateTopicForm';

// Prevent the autosize textarea from flooding the console with errors.
window.scrollTo = vi.fn();

// Suppress "Error: Not implemented: navigation (except hash changes)".
console.error = vi.fn();

describe('Component: CreateTopicForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<CreateTopicForm onPreview={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has not entered any text, disables the preview and submit buttons', () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the user enters text in both fields, enables the preview and submit buttons', async () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />);

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello world');

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /submit/i })).toBeEnabled();
  });

  it('given the user clicks preview, calls the preview callback with the current text', async () => {
    // ARRANGE
    const previewSpy = vi.fn();

    render(<CreateTopicForm onPreview={previewSpy} />);

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello world');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(previewSpy).toHaveBeenCalledWith('Hello world');
  });

  it('given the title is too short, displays an error message', async () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum: createForum() },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your new topic's title/i), 'A');
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the user submits the form, makes the correct POST call to the server', async () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum },
    });

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic.store', { category: category.id, forum: forum.id }),
        {
          title: 'Test Title',
          body: 'Test Body',
          postAsUserId: null,
        },
      );
    });
  });

  it('given the form submission succeeds, redirects to the new topic', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum },
    });

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(visitSpy).toHaveBeenCalledWith(['forum-topic.show', { topic: 789 }]);
    });
  });

  it('shows the current character count and maximum allowed characters', async () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum: createForum() },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello');

    // ASSERT
    expect(screen.getByText(/5.*60,000/)).toBeVisible();
  });

  it('given the user presses Cmd+Enter while focused in the form, submits the form', async () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum },
    });

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'Test Body');
    await userEvent.keyboard('{Meta>}{Enter}{/Meta}');

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic.store', { category: category.id, forum: forum.id }),
        {
          title: 'Test Title',
          body: 'Test Body',
          postAsUserId: null,
        },
      );
    });
  });

  it("given the user has accessible team accounts, shows the 'Post as' select control", () => {
    // ARRANGE
    const teamAccount1 = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    const teamAccount2 = createUser({
      id: 1002,
      displayName: 'DevCompliance',
      avatarUrl: 'https://example.com/dev-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forum: createForum(),
        accessibleTeamAccounts: [teamAccount1, teamAccount2], // !!
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/post as/i)).toBeVisible();
    expect(screen.getByRole('combobox', { name: /post as/i })).toBeVisible();
  });

  it("given the user has no accessible team accounts, does not show the 'Post as' select control", () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forum: createForum(),
        accessibleTeamAccounts: null, // !!
      },
    });

    // ASSERT
    expect(screen.queryByLabelText(/post as/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('combobox', { name: /post as/i })).not.toBeInTheDocument();
  });

  it('given the user selects a team account, shows the team avatar in the submit button', async () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user },
        forum: createForum(),
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ... fill in required fields to make the form valid ...
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');

    // ACT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    await userEvent.selectOptions(postAsSelector, '1001');

    // ASSERT
    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).getByRole('img', { hidden: true });
    expect(avatarImage).toHaveAttribute('src', 'https://example.com/radmin-avatar.png');
    expect(avatarImage).toHaveAttribute('alt', 'RAdmin');
  });

  it('given the user has team accounts but posts as self, does not show an avatar', async () => {
    // ARRANGE
    const user = createAuthenticatedUser({
      displayName: 'Scott',
      avatarUrl: 'https://example.com/scott-avatar.png',
    });
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user },
        forum: createForum(),
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ... fill in required fields to make the form valid ...
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');

    // ASSERT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    expect(postAsSelector).toHaveValue('self'); // !! default is 'self'

    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).queryByRole('img', { hidden: true });
    expect(avatarImage).not.toBeInTheDocument(); // !! no avatar when posting as self
  });

  it('given the user selects a team account, shows the correct submit button text', async () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user },
        forum: createForum(),
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ... fill in required fields to make form valid ...
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');

    // ACT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    await userEvent.selectOptions(postAsSelector, '1001');

    // ASSERT
    expect(screen.getByRole('button', { name: /submit as radmin/i })).toBeVisible();
  });

  it('given the user submits as a team account, sends the correct postAsUserId to the back-end', async () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forum,
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ACT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    await userEvent.selectOptions(postAsSelector, '1001');

    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic.store', { category: category.id, forum: forum.id }),
        {
          title: 'Test Title',
          body: 'Test Body',
          postAsUserId: 1001, // !!
        },
      );
    });
  });

  it('given the user submits as self when team accounts are available, sends null postAsUserId to the back-end', async () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forum,
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ACT
    // ... don't change the select control value, keep it as 'self' ...
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic.store', { category: category.id, forum: forum.id }),
        {
          title: 'Test Title',
          body: 'Test Body',
          postAsUserId: null, // !!
        },
      );
    });
  });

  it('given there are selectable team accounts, sorts them alphabetically in the select control options', () => {
    // ARRANGE
    const teamAccount1 = createUser({
      id: 1001,
      displayName: 'ZetaTeam',
      avatarUrl: 'https://example.com/zeta-avatar.png',
    });

    const teamAccount2 = createUser({
      id: 1002,
      displayName: 'AlphaTeam',
      avatarUrl: 'https://example.com/alpha-avatar.png',
    });

    const teamAccount3 = createUser({
      id: 1003,
      displayName: 'BetaTeam',
      avatarUrl: 'https://example.com/beta-avatar.png',
    });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        forum: createForum(),
        accessibleTeamAccounts: [teamAccount1, teamAccount2, teamAccount3], // !! unsorted
      },
    });

    // ASSERT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    const options = within(postAsSelector).getAllByRole('option');

    expect(options[0]).toHaveTextContent('Scott'); // !! the user's own account is always first

    expect(options[1]).toHaveTextContent('AlphaTeam');
    expect(options[2]).toHaveTextContent('BetaTeam');
    expect(options[3]).toHaveTextContent('ZetaTeam');
  });
});
