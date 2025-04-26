import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import * as UseShortcodeBodyPreviewModule from '@/common/hooks/useShortcodeBodyPreview';
import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createForum,
  createForumCategory,
  createForumTopic,
  createForumTopicComment,
  createPaginatedData,
  createUser,
  createZiggyProps,
} from '@/test/factories';

import { ShowForumTopicMainRoot } from './ShowForumTopicMainRoot';

// Prevent the autosize textarea from flooding the console with errors.
window.scrollTo = vi.fn();

describe('Component: ShowForumTopicMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    const { container } = render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user can update the forum topic, shows topic options', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: { updateForumTopic: true },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /options/i })).toBeVisible();
  });

  it('given the user cannot update the forum topic, does not show topic options', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: { updateForumTopic: false },
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /options/i })).not.toBeInTheDocument();
  });

  it('given the user is authenticated, shows the subscribe toggle button', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /subscribe/i })).toBeVisible();
  });

  it('given user is not authenticated, does not show subscribe toggle button', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        auth: null,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /subscribe/i })).not.toBeInTheDocument();
  });

  it('given the user selects a new page, navigates to that page', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementation(vi.fn());

    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([createForumTopicComment()], {
      perPage: 1,
      currentPage: 1,
      lastPage: 2,
      links: {
        previousPageUrl: null,
        firstPageUrl: null,
        nextPageUrl: '#',
        lastPageUrl: '#',
      },
    });

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const comboboxEl = screen.getAllByRole('combobox')[0];
    await userEvent.click(comboboxEl);
    await userEvent.selectOptions(comboboxEl, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledWith(
      route('forum-topic.show', { topic: forumTopic.id, _query: { page: 2 } }),
    );
  });

  it('given the user is authenticated but muted, shows a muted message instead of the reply form', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    const mutedUntil = new Date(Date.now() + 86400000).toISOString();

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ isMuted: true, mutedUntil }) },
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/you are muted/i)).toBeVisible();
    expect(screen.queryByPlaceholderText(/roms/i)).not.toBeInTheDocument();
  });

  it('given the user is not authenticated, shows a sign in link', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /sign in/i })).toBeVisible();
  });

  it('given preview content exists, displays it', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const paginatedForumTopicComments = createPaginatedData([]);

    const mockPreviewContent = 'Test preview content.';

    vi.spyOn(UseShortcodeBodyPreviewModule, 'useShortcodeBodyPreview').mockReturnValue({
      initiatePreview: vi.fn(),
      previewContent: mockPreviewContent,
    } as any);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByTestId('preview-content')).toBeVisible();
    expect(screen.getByText(mockPreviewContent)).toBeVisible();
  });

  it('given the user is not authenticated, does not show edit button on their posts', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const comment = createForumTopicComment({ user: createUser({ displayName: 'TestUser' }) });
    const paginatedForumTopicComments = createPaginatedData([comment]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /edit/i })).not.toBeInTheDocument();
  });

  it('given the user is muted, does not show edit button on their posts', () => {
    // ARRANGE
    const user = createAuthenticatedUser({
      displayName: 'TestUser',
      isMuted: true, // !!
    });

    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });

    const comment = createForumTopicComment({ user: createUser({ displayName: 'TestUser' }) });
    const paginatedForumTopicComments = createPaginatedData([comment]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        auth: { user },
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /edit/i })).not.toBeInTheDocument();
  });

  it('given the user is the post author and not muted, shows edit button on their posts', () => {
    // ARRANGE
    const user = createAuthenticatedUser({
      displayName: 'TestUser',
      isMuted: false, // !!
    });

    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });

    const comment = createForumTopicComment({ user: createUser({ displayName: 'TestUser' }) });
    const paginatedForumTopicComments = createPaginatedData([comment]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        auth: { user },
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /edit/i })).toBeVisible();
  });

  it('given the topic is locked, shows a message indicating it is locked and disables replying+editing', () => {
    // ARRANGE
    const user = createAuthenticatedUser({
      displayName: 'TestUser',
    });

    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum, lockedAt: new Date().toISOString() });

    const comment = createForumTopicComment({ user: createUser({ displayName: 'TestUser' }) });
    const paginatedForumTopicComments = createPaginatedData([comment]);

    render(<ShowForumTopicMainRoot />, {
      pageProps: {
        auth: { user },
        forumTopic,
        paginatedForumTopicComments,
        can: {},
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByText(/this topic is locked/i)).toBeVisible();
    expect(screen.queryByRole('link', { name: /edit/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });
});
