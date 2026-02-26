import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createEventAward, createGame, createRaEvent } from '@/test/factories';

import { PreferredTierButton } from './PreferredTierButton';

describe('Component: PreferredTierButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAwards: [
        createEventAward({ tierIndex: 1, earnedAt: '2024-01-01' }),
        createEventAward({ tierIndex: 2, earnedAt: '2024-01-02' }),
      ],
    });

    const { container } = render(<PreferredTierButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        event,
        preferredEventAwardTier: null,
        earnedEventAwardTier: 2,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, does not render the button', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAwards: [
        createEventAward({ tierIndex: 1, earnedAt: '2024-01-01' }),
        createEventAward({ tierIndex: 2, earnedAt: '2024-01-02' }),
      ],
    });

    render(<PreferredTierButton />, {
      pageProps: {
        auth: null,
        event,
        preferredEventAwardTier: null,
        earnedEventAwardTier: 2,
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /preferred event award/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user has not earned more than one tier, does not render the button', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAwards: [createEventAward({ tierIndex: 1 }), createEventAward({ tierIndex: 2 })],
    });

    render(<PreferredTierButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        event,
        preferredEventAwardTier: null,
        earnedEventAwardTier: 1,
      },
    });

    // ASSERT
    expect(
      screen.queryByRole('button', { name: /preferred event award/i }),
    ).not.toBeInTheDocument();
  });

  it('given the user has earned multiple tiers, renders the button', () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAwards: [createEventAward({ tierIndex: 1 }), createEventAward({ tierIndex: 2 })],
    });

    render(<PreferredTierButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        event,
        preferredEventAwardTier: null,
        earnedEventAwardTier: 2,
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /preferred event award/i })).toBeVisible();
  });

  it('given the button is clicked, opens the dialog', async () => {
    // ARRANGE
    const event = createRaEvent({
      legacyGame: createGame(),
      eventAwards: [createEventAward({ tierIndex: 1 }), createEventAward({ tierIndex: 2 })],
    });

    render(<PreferredTierButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        event,
        preferredEventAwardTier: null,
        earnedEventAwardTier: 2,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /preferred event award/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
  });

  it('given the form is submitted successfully, closes the dialog', async () => {
    // ARRANGE
    vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: { success: true } });

    const event = createRaEvent({
      id: 7,
      legacyGame: createGame(),
      eventAwards: [
        createEventAward({ tierIndex: 1, label: 'Bronze' }),
        createEventAward({ tierIndex: 2, label: 'Silver' }),
      ],
    });

    render(<PreferredTierButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        event,
        preferredEventAwardTier: null,
        earnedEventAwardTier: 2,
      },
    });

    await userEvent.click(screen.getByRole('button', { name: /preferred event award/i }));
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /bronze/i }));
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });
});
