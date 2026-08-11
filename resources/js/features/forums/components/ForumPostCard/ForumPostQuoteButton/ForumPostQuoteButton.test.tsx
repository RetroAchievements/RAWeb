import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createForumTopic, createForumTopicComment, createUser } from '@/test/factories';

import { ForumPostQuoteButton } from './ForumPostQuoteButton';

describe('Component: ForumPostQuoteButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const topic = createForumTopic();
    const comment = createForumTopicComment();

    const { container } = render(
      <ForumPostQuoteButton comment={comment} topic={topic} onQuote={vi.fn()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user clicks the button, calls onQuote with the composed citation', async () => {
    // ARRANGE
    const onQuote = vi.fn();

    const topic = createForumTopic({ id: 123 });
    const comment = createForumTopicComment({
      id: 456,
      body: 'This is the original post body.',
      user: createUser({ displayName: 'Scott' }),
    });

    render(<ForumPostQuoteButton comment={comment} topic={topic} onQuote={onQuote} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quote post/i }));

    // ASSERT
    expect(onQuote).toHaveBeenCalledWith(
      expect.stringContaining('This is the original post body.'),
    );
    expect(onQuote).toHaveBeenCalledWith(expect.stringContaining('Scott wrote:'));
    expect(onQuote).toHaveBeenCalledWith(expect.stringContaining('forum-topic.show'));
    expect(onQuote).toHaveBeenCalledWith(expect.stringContaining('[quote]'));
    expect(onQuote).toHaveBeenCalledWith(expect.stringContaining('[/quote]'));
  });

  it('given a comment with a different display name, includes that name in the composed citation', async () => {
    // ARRANGE
    const onQuote = vi.fn();

    const topic = createForumTopic();
    const comment = createForumTopicComment({ user: createUser({ displayName: 'MaxMilyin' }) });

    render(<ForumPostQuoteButton comment={comment} topic={topic} onQuote={onQuote} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quote post/i }));

    // ASSERT
    expect(onQuote).toHaveBeenCalledWith(expect.stringContaining('MaxMilyin wrote:'));
  });
});
