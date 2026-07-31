import { faker } from '@faker-js/faker';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser, createAuthenticatedUserPreferences } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createComment, createUser } from '@/test/factories';

import { CommentList } from './CommentList';

describe('Component: CommentList', () => {
  beforeEach(() => {
    sessionStorage.clear();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no comments, displays an empty state message', () => {
    // ARRANGE
    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
    );

    // ASSERT
    expect(screen.getByText(/no comments yet/i)).toBeVisible();
  });

  it('given the current user is muted, tells them and does not show an input field', () => {
    // ARRANGE
    render(
      <CommentList
        canComment={false}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              isMuted: true,
              mutedUntil: faker.date.future().toISOString(),
            }),
          },
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/you are muted until/i)).toBeVisible();
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('given the current user is not muted, displays an input field', () => {
    // ARRANGE
    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              isMuted: false,
              mutedUntil: null,
            }),
          },
        },
      },
    );

    // ASSERT
    expect(screen.queryByText(/you are muted until/i)).not.toBeInTheDocument();
    expect(screen.getByRole('textbox', { name: /comment/i })).toBeVisible();
  });

  it('given the current user is unauthenticated, prompts them to sign in', () => {
    // ARRANGE
    render(
      <CommentList
        canComment={false}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      {
        pageProps: { auth: null },
      },
    );

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /sign in/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'login');
  });

  it('given there are comments, displays them', () => {
    // ARRANGE
    const comments = [
      createComment({ payload: 'foo12345' }),
      createComment({ payload: 'bar12345' }),
      createComment({ payload: 'baz12345' }),
    ];

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={comments}
      />,
    );

    // ASSERT
    expect(screen.getByText(/foo12345/i)).toBeVisible();
    expect(screen.getByText(/bar12345/i)).toBeVisible();
    expect(screen.getByText(/baz12345/i)).toBeVisible();
  });

  it('given the user prefers absolute dates, shows absolute comment post dates', () => {
    // ARRANGE
    const comments = [
      createComment({ payload: 'bar', createdAt: new Date('2023-05-05').toISOString() }),
    ];

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={comments}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              preferences: createAuthenticatedUserPreferences({
                prefersAbsoluteDates: true,
                shouldAlwaysBypassContentWarnings: false,
              }),
            }),
          },
        },
      },
    );

    // ASSERT
    expect(screen.getByText('May 05, 2023, 00:00')).toBeVisible();
  });

  it('given a comment can be deleted, shows a delete button', () => {
    // ARRANGE
    const comments = [
      createComment({ payload: 'foo12345' }),
      createComment({ payload: 'bar12345', canDelete: true }),
      createComment({ payload: 'baz12345' }),
    ];

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={comments}
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('button', { name: /delete/i }).length).toEqual(1);
  });

  it('given a comment can be deleted and the user presses the button and does not confirm deletion, does not make a request to delete the comment', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);

    const comments = [
      createComment({ payload: 'foo12345' }),
      createComment({ payload: 'bar12345', canDelete: true }),
      createComment({ payload: 'baz12345' }),
    ];

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={comments}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(deleteSpy).not.toHaveBeenCalled();
  });

  it('given a comment can be deleted and the user presses the button and confirms deletion, makes a request to delete the comment', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);

    const comments = [
      createComment({ payload: 'foo12345' }),
      createComment({ payload: 'bar12345', canDelete: true }),
      createComment({ payload: 'baz12345' }),
    ];

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={comments}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledOnce();
    expect(deleteSpy).toHaveBeenCalledWith([
      'api.game.comment.destroy',
      {
        comment: comments[1].id,
        game: comments[1].commentableId,
      },
    ]);
  });

  it('given a user types a new comment and presses submit, makes an API call to submit the comment', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /comment/i }), 'this is my comment');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy).toHaveBeenCalledWith(['api.game.comment.store', { game: 1 }], {
      body: 'this is my comment',
      commentableId: 1,
      commentableType: 'game.comment',
    });
  });

  it('given the form is invalid, the submit button is disabled', async () => {
    // ARRANGE
    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    await userEvent.type(screen.getByRole('textbox', { name: /comment/i }), 'aa');

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the comment is an automated comment, does not show an avatar or a username', () => {
    // ARRANGE
    const comments = [
      createComment({
        payload: 'Scott demoted this achievement',
        isAutomated: true,
        user: createUser({ displayName: 'Server' }),
      }),
    ];

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={comments}
      />,
    );

    // ASSERT
    expect(screen.getByText(/scott demoted this achievement/i)).toBeVisible();
    expect(screen.queryByText('Server')).not.toBeInTheDocument();
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('given the user presses Cmd+Enter while focused in the comment form, submits the comment', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ACT
    const textArea = screen.getByRole('textbox', { name: /comment/i });
    await userEvent.type(textArea, 'this is my comment');
    await userEvent.keyboard('{Meta>}{Enter}{/Meta}');

    // ASSERT
    expect(postSpy).toHaveBeenCalledOnce();
    expect(postSpy).toHaveBeenCalledWith(['api.game.comment.store', { game: 1 }], {
      body: 'this is my comment',
      commentableId: 1,
      commentableType: 'game.comment',
    });
  });

  it('given the user types a comment and the form unmounts, restores the draft on the next mount', async () => {
    // ARRANGE
    const pageProps = { auth: { user: createAuthenticatedUser() } };

    const { unmount } = render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      { pageProps },
    );

    await userEvent.type(
      screen.getByRole('textbox', { name: /comment/i }),
      'this comment is not finished',
    );

    // ACT
    unmount();

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      { pageProps },
    );

    // ASSERT
    expect(screen.getByRole('textbox', { name: /comment/i })).toHaveValue(
      'this comment is not finished',
    );
  });

  it('given the user submits their comment, does not restore it as a draft afterwards', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });

    const pageProps = { auth: { user: createAuthenticatedUser() } };

    const { unmount } = render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      { pageProps },
    );

    await userEvent.type(screen.getByRole('textbox', { name: /comment/i }), 'this is my comment');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    await waitFor(() => {
      expect(screen.getByRole('textbox', { name: /comment/i })).toHaveValue('');
    });

    // ACT
    unmount();

    render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      { pageProps },
    );

    // ASSERT
    expect(screen.getByRole('textbox', { name: /comment/i })).toHaveValue('');
  });

  it('given a draft was written for another commentable, does not restore it', async () => {
    // ARRANGE
    const pageProps = { auth: { user: createAuthenticatedUser() } };

    const { unmount } = render(
      <CommentList
        canComment={true}
        commentableId={1}
        commentableType="game.comment"
        comments={[]}
      />,
      { pageProps },
    );

    await userEvent.type(screen.getByRole('textbox', { name: /comment/i }), 'a draft for game 1');
    unmount();

    // ACT
    render(
      <CommentList
        canComment={true}
        commentableId={2}
        commentableType="game.comment"
        comments={[]}
      />,
      { pageProps },
    );

    // ASSERT
    expect(screen.getByRole('textbox', { name: /comment/i })).toHaveValue('');
  });
});
