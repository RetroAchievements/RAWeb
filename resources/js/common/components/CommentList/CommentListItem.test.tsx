import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createComment, createUser, createZiggyProps } from '@/test/factories';

import { CommentListProvider } from './CommentListContext';
import { CommentListItem } from './CommentListItem';

describe('Component: CommentListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const comment = createComment();

    const { container } = render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given user can report, shows the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true, // !!
      commentableType: 'user.comment',
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.getByRole('link', { name: /report/i });
    expect(reportButton).toBeVisible();
  });

  it('given user cannot create reports, hides the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: false, // !!
      commentableType: 'user.comment',
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.queryByRole('link', { name: /report/i });
    expect(reportButton).not.toBeInTheDocument();
  });

  it('given the comment is automated, hides the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'user.comment',
      isAutomated: true, // !!
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.queryByRole('link', { name: /report/i });
    expect(reportButton).not.toBeInTheDocument();
  });

  it('given commentableType is user-moderation.comment, hides the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'user-moderation.comment', // !!
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.queryByRole('link', { name: /report/i });
    expect(reportButton).not.toBeInTheDocument();
  });

  it('given commentableType is game-hash.comment, hides the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'game-hash.comment', // !!
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.queryByRole('link', { name: /report/i });
    expect(reportButton).not.toBeInTheDocument();
  });

  it('given commentableType is achievement-set-claim.comment, hides the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'achievement-set-claim.comment', // !!
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.queryByRole('link', { name: /report/i });
    expect(reportButton).not.toBeInTheDocument();
  });

  it('given commentableType is game-modification.comment, hides the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'game-modification.comment', // !!
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportButton = screen.queryByRole('link', { name: /report/i });
    expect(reportButton).not.toBeInTheDocument();
  });

  it('given the user can report and the comment has the game.comment type, shows the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'game.comment', // !!
      user: createUser({ displayName: 'ReportedUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportIcon = screen.getByLabelText(/report/i);
    expect(reportIcon).toBeVisible();
  });

  it('given the user can report and the comment has the achievement.comment type, shows the report button', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'achievement.comment', // !!
      user: createUser({ displayName: 'ReportedUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      },
    );

    // ASSERT
    const reportIcon = screen.getByLabelText(/report/i);
    expect(reportIcon).toBeVisible();
  });

  it('given the user is using a mobile device, shows the report button in the mobile actions area', () => {
    // ARRANGE
    const comment = createComment({
      canReport: true,
      commentableType: 'user.comment',
      user: createUser({ displayName: 'SomeUser' }),
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          ziggy: createZiggyProps({ device: 'mobile' }), // !!
        },
      },
    );

    // ASSERT
    const reportButton = screen.getByRole('link', { name: /report/i });
    expect(reportButton).toBeVisible();
  });

  it('given user can delete the comment, shows the delete button', () => {
    // ARRANGE
    const comment = createComment({
      canDelete: true, // !!
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
        },
      },
    );

    // ASSERT
    const deleteButton = screen.getByRole('button', { name: /delete/i });
    expect(deleteButton).toBeVisible();
  });

  it('given user cannot delete the comment, hides the delete button', () => {
    // ARRANGE
    const comment = createComment({
      canDelete: false, // !!
    });

    render(
      <CommentListProvider
        canComment={false}
        commentableId={1}
        commentableType="user.comment"
        onDeleteSuccess={vi.fn()}
      >
        <CommentListItem {...comment} />
      </CommentListProvider>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
        },
      },
    );

    // ASSERT
    const deleteButton = screen.queryByRole('button', { name: /delete/i });
    expect(deleteButton).not.toBeInTheDocument();
  });
});
