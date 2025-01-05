import { render, screen } from '@/test';
import { createPlayerGameActivitySummary, createPlayerGameClientBreakdown } from '@/test/factories';

import { UserGameActivityClientBreakdown } from './UserGameActivityClientBreakdown';

describe('Component: UserGameActivityClientBreakdown', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.PlayerGameActivityPageProps>(
      <UserGameActivityClientBreakdown />,
      {
        pageProps: {
          activity: {
            clientBreakdown: [],
            sessions: [],
            summarizedActivity: createPlayerGameActivitySummary(),
          },
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no client data, shows the correct message', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityClientBreakdown />, {
      pageProps: {
        activity: {
          clientBreakdown: [],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/no emulator usage data is available/i));
  });

  it('given there are two clients, displays both directly', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityClientBreakdown />, {
      pageProps: {
        activity: {
          clientBreakdown: [
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RALibRetro',
              durationPercentage: 65.5,
            }),
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RAQUASI88',
              durationPercentage: 34.5,
            }),
          ],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/65.50% - RALibretro/i)).toBeVisible();
    expect(screen.getByText(/34.50% - RAQUASI88/i)).toBeVisible();
  });

  it('given there are many clients, shows the top two with remainder in a tooltip', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityClientBreakdown />, {
      pageProps: {
        activity: {
          clientBreakdown: [
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RALibRetro',
              durationPercentage: 50,
            }),
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RAQUASI88',
              durationPercentage: 25,
            }),
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RetroArch',
              durationPercentage: 15,
            }),
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RANes',
              durationPercentage: 10,
            }),
          ],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/50.00% - RALibretro/i)).toBeVisible();
    expect(screen.getByText(/25.00% - RAQUASI88/i)).toBeVisible();
    expect(screen.getByText(/\+2 more/i)).toBeVisible();
  });

  it('given there are exactly three clients, shows them all directly', () => {
    // ARRANGE
    render<App.Platform.Data.PlayerGameActivityPageProps>(<UserGameActivityClientBreakdown />, {
      pageProps: {
        activity: {
          clientBreakdown: [
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RALibRetro',
              durationPercentage: 50,
            }),
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RAQUASI88',
              durationPercentage: 25,
            }),
            createPlayerGameClientBreakdown({
              clientIdentifier: 'RetroArch',
              durationPercentage: 25,
            }),
          ],
          sessions: [],
          summarizedActivity: createPlayerGameActivitySummary(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/50.00% - RALibretro/i)).toBeVisible();
    expect(screen.getByText(/25.00% - RAQUASI88/i)).toBeVisible();
    expect(screen.getByText(/25.00% - RetroArch/i)).toBeVisible();

    expect(screen.queryByText(/more/i)).not.toBeInTheDocument();
  });
});
