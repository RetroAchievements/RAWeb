import { ArticleType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createComment, createUser } from '@/test/factories';

import { ReportCommentButton } from './ReportCommentButton';

describe('Component: ReportCommentButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const comment = createComment({
      id: 1,
      commentableType: ArticleType.Game,
      user: createUser({
        displayName: 'TestUser',
      }),
    });

    const { container } = render(<ReportCommentButton comment={comment} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a comment, renders a report button', () => {
    // ARRANGE
    const comment = createComment({
      id: 1,
      commentableType: ArticleType.Game,
      user: createUser({
        displayName: 'TestUser',
      }),
    });

    render(<ReportCommentButton comment={comment} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /report/i })).toBeVisible();
  });

  it('given a game wall comment, generates a link', () => {
    // ARRANGE
    const comment = createComment({
      id: 123,
      commentableType: ArticleType.Game, // !!
      user: createUser({
        displayName: 'TestUser',
      }),
    });

    render(<ReportCommentButton comment={comment} />);

    // ASSERT
    const link = screen.getByRole('link');
    expect(link).toHaveAttribute('href', expect.stringContaining('message-thread.create'));
  });

  it('given an unknown comment type, does not crash', () => {
    // ARRANGE
    const comment = createComment({
      id: 999,
      commentableType: 999, // !! unknown
      user: createUser({
        displayName: 'UnknownUser',
      }),
    });

    render(<ReportCommentButton comment={comment} />);

    // ASSERT
    const link = screen.getByRole('link');
    expect(link).toHaveAttribute('href', expect.stringContaining('message-thread.create'));
  });

  it('given a custom className, applies it to the link', () => {
    // ARRANGE
    const comment = createComment({
      id: 1,
      commentableType: ArticleType.Game,
      user: createUser({
        displayName: 'TestUser',
      }),
    });

    render(<ReportCommentButton comment={comment} className="custom-class" />);

    // ASSERT
    expect(screen.getByRole('link')).toHaveClass('custom-class');
  });
});
