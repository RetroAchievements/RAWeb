import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createEventAward, createGame, createRaEvent } from '@/test/factories';

import { PreferredTierForm } from './PreferredTierForm';

describe('Component: PreferredTierForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame() });
    const eventAwards = [
      createEventAward({ tierIndex: 0, label: 'Bronze' }),
      createEventAward({ tierIndex: 1, label: 'Silver' }),
    ];

    const { container } = render(
      <PreferredTierForm
        earnedTierIndex={1}
        event={event}
        eventAwards={eventAwards}
        initialTierIndex={1}
        onSubmitSuccess={vi.fn()}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('only shows tiers the user has earned', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame() });
    const eventAwards = [
      createEventAward({ tierIndex: 0, label: 'Bronze' }),
      createEventAward({ tierIndex: 1, label: 'Silver' }),
      createEventAward({ tierIndex: 2, label: 'Gold' }),
    ];

    render(
      <PreferredTierForm
        earnedTierIndex={1}
        event={event}
        eventAwards={eventAwards}
        initialTierIndex={1}
        onSubmitSuccess={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /bronze/i })).toBeVisible();
    expect(screen.getByRole('radio', { name: /silver/i })).toBeVisible();
    expect(screen.queryByRole('radio', { name: /gold/i })).not.toBeInTheDocument();
  });

  it('displays the save button as disabled by default since the form is not dirty', () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame() });
    const eventAwards = [
      createEventAward({ tierIndex: 0, label: 'Bronze' }),
      createEventAward({ tierIndex: 1, label: 'Silver' }),
    ];

    render(
      <PreferredTierForm
        earnedTierIndex={1}
        event={event}
        eventAwards={eventAwards}
        initialTierIndex={1}
        onSubmitSuccess={vi.fn()}
      />,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /save/i })).toBeDisabled();
  });

  it('enables the save button after selecting a different tier', async () => {
    // ARRANGE
    const event = createRaEvent({ legacyGame: createGame() });
    const eventAwards = [
      createEventAward({ tierIndex: 0, label: 'Bronze' }),
      createEventAward({ tierIndex: 1, label: 'Silver' }),
    ];

    render(
      <PreferredTierForm
        earnedTierIndex={1}
        event={event}
        eventAwards={eventAwards}
        initialTierIndex={1}
        onSubmitSuccess={vi.fn()}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /bronze/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /save/i })).toBeEnabled();
  });

  it('given the form is submitted, calls the mutation', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: { success: true } });
    const onSubmitSuccess = vi.fn();

    const event = createRaEvent({ id: 7, legacyGame: createGame() });
    const eventAwards = [
      createEventAward({ tierIndex: 0, label: 'Bronze' }),
      createEventAward({ tierIndex: 1, label: 'Silver' }),
    ];

    render(
      <PreferredTierForm
        earnedTierIndex={1}
        event={event}
        eventAwards={eventAwards}
        initialTierIndex={1}
        onSubmitSuccess={onSubmitSuccess}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /bronze/i }));
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(putSpy).toHaveBeenCalledOnce();
    });

    expect(putSpy.mock.calls[0][1]).toEqual(expect.objectContaining({ eventId: 7, tierIndex: 0 }));

    await waitFor(() => {
      expect(onSubmitSuccess).toHaveBeenCalled();
    });
  });
});
