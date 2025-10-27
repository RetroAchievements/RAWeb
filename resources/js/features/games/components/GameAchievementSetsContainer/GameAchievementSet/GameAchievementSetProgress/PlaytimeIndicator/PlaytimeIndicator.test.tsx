import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createPlayerGame, createZiggyProps } from '@/test/factories';

import { PlaytimeIndicator } from './PlaytimeIndicator';

describe('Component: PlaytimeIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlaytimeIndicator />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the player has never played the game, renders the correct aria label', () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: null, // !!
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByLabelText('No playtime recorded.')).toBeVisible();
  });

  it('given the player has playtime, renders the correct aria label', () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: createPlayerGame({
          playtimeTotal: 24840,
        }),
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByLabelText('Your Playtime Stats')).toBeVisible();
  });

  it('given the player has zero playtime, renders the correct aria label', () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: createPlayerGame({
          playtimeTotal: 0, // !!
        }),
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getByLabelText('No playtime recorded.')).toBeVisible();
  });

  it('on hover, shows playtime stats when available', async () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: createPlayerGame({
          playtimeTotal: 24840,
          lastPlayedAt: '2024-10-17T12:30:00Z',
        }),
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.hover(screen.getByLabelText('Your Playtime Stats'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/your playtime stats/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/total playtime/i)[0]).toBeVisible();
    expect(screen.getAllByText(/last played/i)[0]).toBeVisible();
    expect(screen.getAllByText(/oct 17, 2024/i)[0]).toBeVisible();
  });

  it('on hover when never played, shows the no playtime message', async () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: null, // !!
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.hover(screen.getByLabelText('No playtime recorded.'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/your playtime stats/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/no playtime recorded/i)[0]).toBeVisible();
    expect(screen.queryByText(/total playtime/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/last played/i)).not.toBeInTheDocument();
  });

  it('uses faded styling when no playtime exists', () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: null, // !!
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const indicator = screen.getByLabelText('No playtime recorded.');
    expect(indicator).toHaveClass('text-neutral-300/30');
  });

  it('uses non-faded styling when playtime exists', () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: createPlayerGame({
          playtimeTotal: 24840, // !!
        }),
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    const indicator = screen.getByLabelText('Your Playtime Stats');
    expect(indicator).toHaveClass('text-neutral-200');
  });

  it('given the player has playtime but no last played date, only shows total playtime', async () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: createPlayerGame({
          playtimeTotal: 3600, // !! 1h
          lastPlayedAt: null, // !!
        }),
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.hover(screen.getByLabelText('Your Playtime Stats'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/total playtime/i)[0]).toBeVisible();
    });
    expect(screen.queryByText(/last played/i)).not.toBeInTheDocument();
  });

  it('given the user is on mobile, renders with a popover instead of a tooltip', async () => {
    // ARRANGE
    render(<PlaytimeIndicator />, {
      pageProps: {
        playerGame: createPlayerGame({
          playtimeTotal: 24840, // !! 6h 54m
          lastPlayedAt: '2024-10-17T12:30:00Z', // !!
        }),
        ziggy: createZiggyProps({ device: 'mobile' }), // !!
      },
    });

    // ACT
    await userEvent.click(screen.getByLabelText('Your Playtime Stats'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/your playtime stats/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/total playtime/i)[0]).toBeVisible();
    expect(screen.getAllByText(/last played/i)[0]).toBeVisible();
  });
});
