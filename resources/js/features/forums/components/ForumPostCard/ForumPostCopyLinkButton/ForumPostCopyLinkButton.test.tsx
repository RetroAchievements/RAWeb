import userEvent from '@testing-library/user-event';
import * as ReactUseModule from 'react-use';

import { render, screen } from '@/test';
import { createForumTopic, createForumTopicComment } from '@/test/factories';

import { ForumPostCopyLinkButton } from './ForumPostCopyLinkButton';

describe('Component: ForumPostCopyLinkButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const topic = createForumTopic();
    const comment = createForumTopicComment();

    const { container } = render(<ForumPostCopyLinkButton comment={comment} topic={topic} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user clicks the button, copies the link to clipboard and shows a success toast', async () => {
    // ARRANGE
    const copyToClipboardSpy = vi.fn();
    vi.spyOn(ReactUseModule, 'useCopyToClipboard').mockReturnValue([
      null as any,
      copyToClipboardSpy,
    ]);

    const topic = createForumTopic({ id: 123 });
    const comment = createForumTopicComment({ id: 456 });

    render(<ForumPostCopyLinkButton comment={comment} topic={topic} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /copy post link/i }));

    // ASSERT
    expect(copyToClipboardSpy).toHaveBeenCalledWith(expect.stringContaining('forum-topic.show'));
    expect(screen.getByText(/copied!/i)).toBeVisible();
  });
});
