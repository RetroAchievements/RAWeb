import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor, within } from '@/test';
import { createAchievement, createLeaderboard, createTicketListEntry } from '@/test/factories';

import { TicketCloseDialog } from './TicketCloseDialog';

describe('Component: TicketCloseDialog', () => {
  it('given a note was already typed, it survives a switch of the close reason', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['closed-mistaken', 'wrong-rom'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Player error or works as designed' }));
    await userEvent.type(
      screen.getByLabelText(/^Note for the reporter/),
      'You need to finish the stage without taking damage.',
    );
    await userEvent.click(screen.getByRole('radio', { name: 'Wrong ROM' }));

    // ASSERT
    expect(screen.getByLabelText(/^Note for the reporter/)).toHaveValue(
      'You need to finish the stage without taking damage.',
    );
  });

  it('given the first answer changes before any note is typed, the reason and the note are both cleared', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['wrong-rom', 'unable-to-reproduce'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Wrong ROM' }));
    await userEvent.click(screen.getByRole('radio', { name: "Can't tell" }));

    // ASSERT
    expect(screen.getByRole('radio', { name: 'Unable to reproduce' })).not.toBeChecked();
    expect(screen.getByLabelText(/^Note for the reporter/)).toHaveValue('');
    expect(screen.getByLabelText(/^Note for the reporter/)).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Close ticket' })).toBeDisabled();
  });

  it('given "Something else" is picked, the submit button waits for a note to be typed', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['wrong-rom', 'closed-other'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Wrong ROM' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Something else' }));

    // ASSERT
    const noteField = screen.getByLabelText('Note for the reporter (required)');
    const submitButton = screen.getByRole('button', { name: 'Close ticket' });
    expect(noteField).toHaveValue('');
    expect(submitButton).toBeDisabled();
    expect(screen.getByText('A note is required to close this ticket.')).toBeVisible();

    await userEvent.type(noteField, 'ok');
    expect(submitButton).toBeDisabled();
    expect(screen.queryByText('Must be at least 3 characters.')).not.toBeInTheDocument();

    await userEvent.tab();
    expect(screen.getByText('Must be at least 3 characters.')).toBeVisible();

    await userEvent.type(noteField, ', this was a duplicate of a forum report.');
    expect(submitButton).toBeEnabled();
  });

  it('given a "Something else" note that describes an emulator problem, that reason is nudged and picking it still persists the note', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['network', 'emulator-problem', 'closed-other'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: "Can't tell" }));
    await userEvent.click(screen.getByRole('radio', { name: 'Something else' }));
    await userEvent.type(
      screen.getByLabelText('Note for the reporter (required)'),
      'Nestopia is not a supported core.',
    );

    // ASSERT
    expect(screen.getByText(/Emulator or core problem\?/)).toBeVisible();

    await userEvent.click(screen.getByRole('button', { name: 'Pick this' }));

    expect(screen.getByRole('radio', { name: 'No' })).toBeChecked();
    expect(screen.getByRole('radio', { name: 'Emulator or core problem' })).toBeChecked();
    expect(screen.getByLabelText('Note for the reporter (optional)')).toHaveValue(
      'Nestopia is not a supported core.',
    );
    expect(screen.queryByRole('button', { name: 'Pick this' })).not.toBeInTheDocument();
  });

  it('given a "Something else" note that names a reason the closer cannot pick, no suggestion gets nudged', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['wrong-rom', 'closed-other'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Something else' }));
    await userEvent.type(
      screen.getByLabelText('Note for the reporter (required)'),
      'Probably a network issue on your end.',
    );

    // ASSERT
    expect(screen.queryByText(/Network problems\?/)).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Pick this' })).not.toBeInTheDocument();
  });

  it('given a promoted achievement and "I\'m demoting it" is picked, shows the correct labels', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement({ isPromoted: true }),
          allowedTicketActions: ['resolved', 'demoted'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'Yes' }));
    await userEvent.click(screen.getByRole('radio', { name: "I'm demoting it" }));

    // ASSERT
    expect(screen.getByText('Submitting will demote this achievement.')).toBeVisible();
    expect(screen.getByRole('button', { name: 'Demote and close' })).toBeEnabled();
  });

  it('given an achievement that is already demoted, shows the correct labels', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement({ isPromoted: false }),
          allowedTicketActions: ['resolved', 'demoted'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'Yes' }));
    await userEvent.click(screen.getByRole('radio', { name: 'It was already demoted' }));

    // ASSERT
    expect(screen.queryByRole('radio', { name: "I'm demoting it" })).not.toBeInTheDocument();
    expect(screen.queryByText(/Submitting will demote/)).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Close ticket' })).toBeEnabled();
  });

  it('given "Unable to debug" is picked, the correct helper text appears', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['network', 'unable-to-debug'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: "Can't tell" }));
    await userEvent.click(
      screen.getByRole('radio', { name: 'Unable to debug (no toolkit support)' }),
    );

    // ASSERT
    expect(
      screen.getByText(
        'If the player gave steps to reproduce the problem, try to reproduce it before you close the ticket.',
      ),
    ).toBeVisible();
  });

  it('given the form is submitted, the action and note are sent and the page reloads', async () => {
    // ARRANGE
    const patchSpy = vi
      .spyOn(axios, 'patch')
      .mockResolvedValueOnce({ data: { message: 'Updated' } });
    const onOpenChange = vi.fn();

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={onOpenChange} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['network'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ id: 7731, state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Network problems' }));
    await userEvent.type(
      screen.getByLabelText(/^Note for the reporter/),
      'Please check your connection and try again.',
    );
    await userEvent.click(screen.getByRole('button', { name: 'Close ticket' }));

    // ASSERT
    expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 7731 }), {
      action: 'network',
      note: 'Please check your connection and try again.',
    });
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalled();
    });
    expect(onOpenChange).toHaveBeenCalledWith(false);
  });

  it('closing the ticket does not submit the comment form', async () => {
    // ARRANGE
    vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: { message: 'Updated' } });
    const outerCommentFormSubmitHandler = vi.fn((formEvent: React.FormEvent) =>
      formEvent.preventDefault(),
    );

    render<App.Platform.Data.TicketShowPageProps>(
      <form onSubmit={outerCommentFormSubmitHandler}>
        <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />
      </form>,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['network'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Network problems' }));
    await userEvent.click(screen.getByRole('button', { name: 'Close ticket' }));

    // ASSERT
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalled();
    });
    expect(outerCommentFormSubmitHandler).not.toHaveBeenCalled();
  });

  it('given the note is empty, no note is sent in the API payload', async () => {
    // ARRANGE
    const patchSpy = vi
      .spyOn(axios, 'patch')
      .mockResolvedValueOnce({ data: { message: 'Updated' } });

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['resolved'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ id: 7731, state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'Yes' }));
    await userEvent.click(screen.getByRole('radio', { name: 'I fixed it' }));
    await userEvent.click(screen.getByRole('button', { name: 'Resolve ticket' }));

    // ASSERT
    expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 7731 }), {
      action: 'resolved',
      note: null,
    });
  });

  it('given the server rejects the request, the correct message appears', async () => {
    // ARRANGE
    vi.spyOn(axios, 'patch').mockRejectedValueOnce({
      response: {
        status: 422,
        data: { message: 'The note field is required when action is closed-other.' },
      },
    });
    const onOpenChange = vi.fn();

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={onOpenChange} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['closed-other'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Something else' }));
    await userEvent.type(
      screen.getByLabelText(/^Note for the reporter/),
      'The forum thread already answers this.',
    );
    await userEvent.click(screen.getByRole('button', { name: 'Close ticket' }));

    // ASSERT
    expect(
      await screen.findByText('The note field is required when action is closed-other.'),
    ).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByLabelText(/^Note for the reporter/)).toHaveValue(
      'The forum thread already answers this.',
    );
    expect(screen.getByRole('radio', { name: 'Something else' })).toBeChecked();
    expect(onOpenChange).not.toHaveBeenCalled();
    expect(router.reload).not.toHaveBeenCalled();
  });

  it('given the server forbids the request, the correct message appears', async () => {
    // ARRANGE
    vi.spyOn(axios, 'patch').mockRejectedValueOnce({
      response: { status: 403, data: { message: 'This action is unauthorized.' } },
    });

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['network'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Network problems' }));
    await userEvent.click(screen.getByRole('button', { name: 'Close ticket' }));

    // ASSERT
    expect(
      await screen.findByText(
        'This ticket changed since you opened it. Reload the page and try again.',
      ),
    ).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
  });

  it('given the dialog closes and reopens, the form is reset', async () => {
    // ARRANGE
    const pageProps: Partial<App.Platform.Data.TicketShowPageProps> = {
      achievement: createAchievement(),
      allowedTicketActions: ['wrong-rom', 'network'],
      can: { createTicketComments: true },
      ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
    };

    const { rerender } = render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      { pageProps },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Wrong ROM' }));
    await userEvent.type(screen.getByLabelText(/^Note for the reporter/), ' Thanks again.');

    rerender(<TicketCloseDialog isOpen={false} onOpenChange={vi.fn()} />);
    rerender(<TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('radio', { name: 'No' })).not.toBeChecked();
    expect(screen.queryByRole('radiogroup', { name: 'What happened?' })).not.toBeInTheDocument();
    expect(screen.getByLabelText(/^Note for the reporter/)).toHaveValue('');
    expect(screen.getByLabelText(/^Note for the reporter/)).toBeDisabled();
  });

  it('given a promoted leaderboard ticket, the labels are all correct', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: null,
          leaderboard: createLeaderboard({ state: 'active' }),
          allowedTicketActions: ['resolved', 'demoted'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'leaderboard' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'Yes' }));
    await userEvent.click(screen.getByRole('radio', { name: "I'm unpromoting it" }));

    // ASSERT
    expect(
      screen.getByRole('radiogroup', { name: 'Was something wrong with the leaderboard?' }),
    ).toBeVisible();
    expect(screen.getByText('Submitting will unpromote this leaderboard.')).toBeVisible();
    expect(screen.getByRole('button', { name: 'Unpromote and close' })).toBeEnabled();
  });

  it('given a leaderboard that is already unpromoted, says so', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: null,
          leaderboard: createLeaderboard({ state: 'unpromoted' }),
          allowedTicketActions: ['resolved', 'demoted'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'leaderboard' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'Yes' }));

    // ASSERT
    expect(screen.getByRole('radio', { name: 'It was already unpromoted' })).toBeVisible();
  });

  it('given the user cannot comment, "Something else" and the note field are gone and no note is recorded', async () => {
    // ARRANGE
    const patchSpy = vi
      .spyOn(axios, 'patch')
      .mockResolvedValueOnce({ data: { message: 'Updated' } });

    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['wrong-rom', 'closed-other', 'unable-to-reproduce'],
          can: { createTicketComments: false },
          ticket: createTicketListEntry({ id: 9214, state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));

    // ASSERT
    const answerGroup = screen.getByRole('radiogroup', {
      name: 'Was something wrong with the achievement?',
    });
    const answers = within(answerGroup).getAllByRole('radio');
    expect(answers).toHaveLength(2);
    expect(answers[0]).toHaveAccessibleName('No');
    expect(answers[1]).toHaveAccessibleName("Can't tell");

    const reasonGroup = screen.getByRole('radiogroup', { name: 'What happened?' });
    const reasons = within(reasonGroup).getAllByRole('radio');
    expect(reasons).toHaveLength(1);
    expect(reasons[0]).toHaveAccessibleName('Wrong ROM');
    expect(screen.queryByLabelText(/^Note for the reporter/)).not.toBeInTheDocument();

    await userEvent.click(reasons[0]);
    await userEvent.click(screen.getByRole('button', { name: 'Close ticket' }));

    expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 9214 }), {
      action: 'wrong-rom',
      note: null,
    });
  });

  it('given "Network problems" is picked, shows the correct hint label', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['wrong-rom', 'network'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Network problems' }));

    // ASSERT
    expect(
      screen.getByText('The reporter will see a button to request a manual unlock.'),
    ).not.toHaveAttribute('aria-hidden', 'true');
  });

  it('given the picked reason is not "Network problems", no manual unlock hint is shown', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: createAchievement(),
          allowedTicketActions: ['wrong-rom', 'network'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Wrong ROM' }));

    // ASSERT
    expect(
      screen.getByText('The reporter will see a button to request a manual unlock.'),
    ).toHaveAttribute('aria-hidden', 'true');
  });

  it('given a leaderboard ticket, no manual unlock hint is shown', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(
      <TicketCloseDialog isOpen={true} onOpenChange={vi.fn()} />,
      {
        pageProps: {
          achievement: null,
          leaderboard: createLeaderboard(),
          allowedTicketActions: ['network'],
          can: { createTicketComments: true },
          ticket: createTicketListEntry({ state: 'open', ticketableType: 'leaderboard' }),
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: 'No' }));
    await userEvent.click(screen.getByRole('radio', { name: 'Network problems' }));

    // ASSERT
    expect(
      screen.queryByText('The reporter will see a button to request a manual unlock.'),
    ).not.toBeInTheDocument();
  });
});
