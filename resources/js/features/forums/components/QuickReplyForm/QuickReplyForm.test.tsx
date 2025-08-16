import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor, within } from '@/test';
import { createForumTopic, createUser } from '@/test/factories';

import { QuickReplyForm } from './QuickReplyForm';

// Suppress "Error: Not implemented: window.scrollTo".
console.error = vi.fn();

// Prevent the autosize textarea from flooding the console with errors.
window.scrollTo = vi.fn();

describe('Component: QuickReplyForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no authenticated user, renders nothing', () => {
    // ARRANGE
    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: null,
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('form')).not.toBeInTheDocument();
  });

  it('given the user types a message and submits the form, creates a new forum topic comment', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { commentId: 123 } });
    const mockAssign = vi.fn();
    Object.defineProperty(window, 'location', { value: { assign: mockAssign }, writable: true });

    const topic = createForumTopic();

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: topic,
      },
    });

    // ACT
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic-comment.create', { topic: topic.id }),
        { body: 'My message', postAsUserId: null },
      );
    });
  });

  it('given the user clicks preview, calls the preview handler with the current message', async () => {
    // ARRANGE
    const previewHandler = vi.fn();

    render(<QuickReplyForm onPreview={previewHandler} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
      },
    });

    // ACT
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(previewHandler).toHaveBeenCalledWith('My message');
  });

  it('given the message is empty, disables the preview button', () => {
    // ARRANGE
    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeDisabled();
  });

  it('given the user presses Cmd+Enter while focused in the form, submits the form', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { commentId: 123 } });
    const mockAssign = vi.fn();
    Object.defineProperty(window, 'location', { value: { assign: mockAssign }, writable: true });

    const topic = createForumTopic();

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: topic,
      },
    });

    // ACT
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.keyboard('{Meta>}{Enter}{/Meta}');

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic-comment.create', { topic: topic.id }),
        { body: 'My message', postAsUserId: null },
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

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
        accessibleTeamAccounts: [teamAccount1, teamAccount2], // !!
      },
    });

    // ASSERT
    expect(screen.getByLabelText(/post as/i)).toBeVisible();
    expect(screen.getByRole('combobox', { name: /post as/i })).toBeVisible();
  });

  it("given the user has no accessible team accounts, does not show the 'Post as' select control", () => {
    // ARRANGE
    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
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

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user },
        forumTopic: createForumTopic(),
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ACT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    await userEvent.selectOptions(postAsSelector, '1001');

    // ASSERT
    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).getByRole('img', { hidden: true });
    expect(avatarImage).toHaveAttribute('src', 'https://example.com/radmin-avatar.png');
    expect(avatarImage).toHaveAttribute('alt', 'RAdmin');
  });

  it("given the user has team accounts but posts as self, shows the user's own avatar", async () => {
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

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user },
        forumTopic: createForumTopic(),
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ASSERT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    expect(postAsSelector).toHaveValue('self'); // !! default is 'self'

    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).getByRole('img', { hidden: true });
    expect(avatarImage).toHaveAttribute('src', 'https://example.com/scott-avatar.png');
    expect(avatarImage).toHaveAttribute('alt', 'Scott');
  });

  it('given the user selects a team account, shows the correct submit button text', async () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user },
        forumTopic: createForumTopic(),
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ACT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    await userEvent.selectOptions(postAsSelector, '1001');

    // ASSERT
    expect(screen.getByRole('button', { name: /submit as radmin/i })).toBeVisible();
  });

  it('given the user submits as a team account, sends the correct postAsUserId to the back-end', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { commentId: 123 } });
    const mockAssign = vi.fn();
    Object.defineProperty(window, 'location', { value: { assign: mockAssign }, writable: true });

    const topic = createForumTopic();
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: topic,
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ACT
    const postAsSelector = screen.getByRole('combobox', { name: /post as/i });
    await userEvent.selectOptions(postAsSelector, '1001');

    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic-comment.create', { topic: topic.id }),
        { body: 'My message', postAsUserId: 1001 }, // !!
      );
    });
  });

  it('given the user submits as self when team accounts are available, sends null postAsUserId to the back-end', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { commentId: 123 } });
    const mockAssign = vi.fn();
    Object.defineProperty(window, 'location', { value: { assign: mockAssign }, writable: true });

    const topic = createForumTopic();
    const teamAccount = createUser({
      id: 1001,
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: topic,
        accessibleTeamAccounts: [teamAccount], // !!
      },
    });

    // ACT
    // ... don't change the select control value, keep it as 'self' ...
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic-comment.create', { topic: topic.id }),
        { body: 'My message', postAsUserId: null }, // !!
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

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        forumTopic: createForumTopic(),
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
