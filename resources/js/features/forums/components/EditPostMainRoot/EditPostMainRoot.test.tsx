import * as UseShortcodeBodyPreviewModule from '@/common/hooks/useShortcodeBodyPreview';
import { render, screen } from '@/test';
import {
  createForum,
  createForumCategory,
  createForumTopic,
  createForumTopicComment,
} from '@/test/factories';

import { EditPostMainRoot } from './EditPostMainRoot';

// Prevent the autosize textarea from flooding the console with errors.
window.scrollTo = vi.fn();

describe('Component: EditPostMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const forumTopicComment = createForumTopicComment({ forumTopic });

    const { container } = render<App.Data.EditForumTopicCommentPageProps>(<EditPostMainRoot />, {
      pageProps: {
        forumTopicComment,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays breadcrumbs', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const forumTopicComment = createForumTopicComment({ forumTopic });

    render<App.Data.EditForumTopicCommentPageProps>(<EditPostMainRoot />, {
      pageProps: {
        forumTopicComment,
      },
    });

    // ASSERT
    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /forum index/i })).toBeVisible();
  });

  it('does not render the preview card when preview content is not present', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const forumTopicComment = createForumTopicComment({ forumTopic });

    render<App.Data.EditForumTopicCommentPageProps>(<EditPostMainRoot />, {
      pageProps: {
        forumTopicComment,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('preview-content')).not.toBeInTheDocument();
  });

  it('given preview content exists, displays it', () => {
    // ARRANGE
    const category = createForumCategory();
    const forum = createForum({ category });
    const forumTopic = createForumTopic({ forum });
    const forumTopicComment = createForumTopicComment({ forumTopic });

    const mockPreviewContent = 'Test preview content.';

    vi.spyOn(UseShortcodeBodyPreviewModule, 'useShortcodeBodyPreview').mockReturnValue({
      initiatePreview: vi.fn(),
      previewContent: mockPreviewContent,
    } as any);

    render<App.Data.EditForumTopicCommentPageProps>(<EditPostMainRoot />, {
      pageProps: {
        forumTopicComment,
      },
    });

    // ASSERT
    expect(screen.getByTestId('preview-content')).toBeVisible();
    expect(screen.getByText(mockPreviewContent)).toBeVisible();
  });
});
