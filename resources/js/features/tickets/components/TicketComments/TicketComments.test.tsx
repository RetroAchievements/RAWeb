import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createComment, createTicketListEntry } from '@/test/factories';

import { TicketComments } from './TicketComments';

describe('Component: TicketComments', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        can: {},
        comments: [],
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the ticket has comments, displays them', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        can: {},
        comments: [
          createComment({ id: 1, payload: 'First comment.' }),
          createComment({ id: 2, payload: 'Second comment.' }),
        ],
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: 'Comments' })).toBeVisible();

    const items = screen.getAllByRole('listitem');
    expect(items[0]).toHaveTextContent('First comment.');
    expect(items[1]).toHaveTextContent('Second comment.');
  });

  it('given the ticket has no comments, shows an empty state', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        can: {},
        comments: [],
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.getByText('No comments yet.')).toBeVisible();
  });

  it('given the ticket has a report body but no comments, does not show the comments component empty state', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        can: {},
        comments: [],
        reportBody: 'It never unlocks.',
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.getByRole('article', { name: 'Report' })).toBeVisible();
    expect(screen.queryByText('No comments yet.')).not.toBeInTheDocument();
  });

  it('given the ticket has a report body, shows the report before the first comment', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        can: {},
        comments: [createComment({ id: 1, payload: 'First comment.' })],
        reportBody: 'It never unlocks.',
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    const report = screen.getByRole('article', { name: 'Report' });
    const firstComment = screen.getByRole('listitem');

    expect(report).toHaveTextContent('It never unlocks.');
    expect(report.compareDocumentPosition(firstComment)).toEqual(Node.DOCUMENT_POSITION_FOLLOWING);
  });

  it('given the user cannot comment, does not let them', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: {},
        comments: [],
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('given the user submits a comment, posts it and reloads the page client-side', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementation(vi.fn());

    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { createTicketComments: true },
        comments: [],
        ticket: createTicketListEntry({ id: 777 }),
      },
    });

    // ACT
    await userEvent.type(screen.getByRole('textbox'), 'I was using the US version.');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(['api.ticket.comment.store', { ticket: 777 }], {
      body: 'I was using the US version.',
      commentableId: 777,
      commentableType: 'trigger.ticket.comment',
    });
    expect(reloadSpy).toHaveBeenCalledWith();
  });

  it('given the user deletes a comment, makes the API call and reloads the page client-side', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementation(vi.fn());

    render<App.Platform.Data.TicketShowPageProps>(<TicketComments />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { createTicketComments: true },
        comments: [
          createComment({
            id: 31,
            canDelete: true,
            commentableId: 777,
            commentableType: 'trigger.ticket.comment',
          }),
        ],
        ticket: createTicketListEntry({ id: 777 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith([
      'api.ticket.comment.destroy',
      { ticket: 777, comment: 31 },
    ]);
    expect(reloadSpy).toHaveBeenCalledWith();
  });
});
